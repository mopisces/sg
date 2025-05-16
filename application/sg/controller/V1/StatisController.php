<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use util;

class StatisController extends Controller
{
	protected $failException = true;

	public function getStatisConfig()
	{
		$result = [];
		$all_isnew = 0;
		foreach( config('app.db_config') as $key => $value ){
			if( $value['isnew'] == 1 ){
				$all_isnew = 1;
			}
		}

		$result['line'][0] = ['text'=>$this->request->lang['all'],'value'=>'ALL', 'isnew'=>$all_isnew];
		foreach ( config('app.db_config') as $key => $value) {
			$result['line'][$key + 1]['text'] = $value['DB_FLAG'];
			$result['line'][$key + 1]['value'] = $key;
			$result['line'][$key + 1]['isnew'] = $value['isnew'];
		}
		$result['date'] = [
			'maxDate'   => date('Y-m-d'),
			'minDate'   => date('Y-m-d',strtotime('-1 year')),
			'beginDate' => date('Y-m-d',strtotime('-1 year')),
			'endDate'   => date('Y-m-d'),
		];
		return ['errorCode'=>'00000','msg'=>$this->request->lang['return'] . $this->request->lang['success'],'result'=>$result];
	}
 
	/**
	 * [getCondition 获取执行条件]
	 * @param  [array] $data [参数]
	 * @return [array]       [执行参数]
	 * good_sqm 良品平方 [良品长度*(门幅-修边)]
	 * total_waste_sqm 总废品面积
	 * total_len 总长度 [生产长度+切废长度(切废+换单切废)] 
	 * avg_speed 平均车速
	 * bad_sqm 坏品平方     [坏品长度*(门幅-修边)]
	 * trim_sqm 修边平方     [(良品长度+坏品长度)*修边]
	 * shift_cutting_waste_sqm 换单切废平方 [0.8米*换单切废数*门幅]  
	 * cutting_waste_sqm 切废平方 [0.8米*切废数*门幅]
	 * stops 停次
	 */
	protected function getCondition( $data )
	{
		/*$condition = [];
		$condition[] = [
			'finish_date',
			'between', 
			[ 
				$data['begin_date'] . ' 00:00:00', 
				$data['end_date'] . ' 23:59:59' 
			] 
		];
		if( $data['class'] != 'ALL' ){
			$condition[] = ['shift_code', '=', $data['class']];
		}*/
		$condition = '';	
		$condition .= ' finish_date between convert(datetime,\''. $data['begin_date'] . ' 00:00:00' .'\', 20)' . 'AND ' .  'convert(datetime,\''. $data['end_date'] . ' 00:00:00' .'\', 20)';
		if( $data['class'] != 'ALL' ){
			$condition .= ' AND  shift_code = \'' .$data['class']. '\'';
		}
		switch ($data['time_type']) {
			case 1:
				$date = 'CONVERT(date, finish_date, 23)';
				break;
			case 2:
				$date = 'datepart(wk,finish_date)';
				break;
			case 3:
				$date = 'datepart(mm,finish_date)';
				break;
			default:
				$date = 'CONVERT(date, finish_date, 23)';
				break;
		}
		switch ($data['statis_type']) {
			case 'sumArea':
				//$field = 'Convert(decimal(18,0),SUM((paper_len/1000.0*(good_qty+bad_qty)+0.8*(shift_cutting_waste_qty+cutting_waste_qty))*width/1000.0)) AS statis_data';
				$field = 'Convert(decimal(18,0),SUM(paper_len/1000.0*good_qty*([width]-[trimming])/1000.0)) as good_sqm,CONVERT(decimal(18,0),( SUM(paper_len/1000.0*bad_qty*([width]-[trimming])/1000.0) + SUM(paper_len/1000.0*(good_qty+bad_qty)*trimming/1000.0) + SUM(0.8*(shift_cutting_waste_qty)*(width)/1000.0) + SUM(0.8*(cutting_waste_qty)*(width)/1000.0)) ) as total_waste_sqm';
				break;
			case 'sumLen':
				$field = 'Convert(decimal(18,0),SUM(paper_len/1000.0*(good_qty+bad_qty)+0.8*(shift_cutting_waste_qty+cutting_waste_qty)))  as total_len';
				break;
			case 'avgSpeed':
				$field = 'CONVERT(decimal(18,0),SUM(paper_len*(good_qty)/1000.0) / (SUM( (work_time+stop_time)*1.0/60 ) +0.001) ) AS avg_speed';
				break;
			case 'sumLoss':
				//$field = 'CONVERT(decimal(18,0),( SUM(paper_len/1000.0*bad_qty*([width]-[trimming])/1000.0) + SUM(paper_len/1000.0*(good_qty+bad_qty)*trimming/1000.0) + SUM(0.8*(shift_cutting_waste_qty)*(width)/1000.0) + SUM(0.8*(cutting_waste_qty)*(width)/1000.0)) )  as statis_data';
				$field = 'CONVERT(decimal(18,0),SUM(paper_len/1000.0*bad_qty*([width]-[trimming])/1000.0) ) as bad_sqm, CONVERT(decimal(18,0), SUM(paper_len/1000.0*(good_qty+bad_qty)*trimming/1000.0)) as trim_sqm,  CONVERT(decimal(18,0), SUM(0.8*(shift_cutting_waste_qty)*(width)/1000.0)) as shift_cutting_waste_sqm, CONVERT(decimal(18,0),SUM(0.8*(cutting_waste_qty)*(width)/1000.0) ) as cutting_waste_sqm';
				break;
			case 'sumStops':
				$field = 'SUM(stops) as stops';
				break;
			default:
				$field = 'Convert(decimal(18,0),SUM(paper_len/1000.0*good_qty*([width]-[trimming])/1000.0)) as good_sqm,CONVERT(decimal(18,0),( SUM(paper_len/1000.0*bad_qty*([width]-[trimming])/1000.0) + SUM(paper_len/1000.0*(good_qty+bad_qty)*trimming/1000.0) + SUM(0.8*(shift_cutting_waste_qty)*(width)/1000.0) + SUM(0.8*(cutting_waste_qty)*(width)/1000.0)) ) as total_waste_sqm';
				break;
		}
		return ['condition'=>$condition, 'field'=>$field, 'date'=>$date];
	}

	public function getStatisData()
	{
		$this->validate( $this->request->post(),'app\sg\validate\StatisValidate.getStatisData' );
		$data = $this->request->post();
		$all = [];
		$final = [];
		if( $data['line'] == 'ALL' ){
			foreach ( config('app.db_config') as $key => $value){
				if( $value['isnew'] == 1 ){
					$data_base = $this->getSqlData(util::getConnect( config('app.db_config')[$key]),  $data );
					if( count($data_base) > 0 ){
						foreach ($data_base as $k => $v) {
							$all[] = $v;
						}
					}
				}
			}
			$final = [];
			if( count($all) > 0 ){
				foreach ($all as $key => $value) {
					if( isset( $all[ $value['statis_date'] ] ) ){
						$field = array_keys($value);
						unset($field['statis_date']);
						unset($field['ROW_NUMBER']);
						foreach ($field as $idx => $item) {
							$final[ $value['statis_date'] ][$item] += $value[$item];
						}
					}else{
						$final[ $value['statis_date'] ] = $value;
					}
				}
			}
			$final = array_values($final);
		}else{
			if( config('app.db_config')[$data['line']]['isnew'] == 1 ){
				$final = $this->getSqlData(util::getConnect( config('app.db_config')[$data['line']] ),  $data );
			}
		}
		return ['errorCode'=>'00000','msg'=>$this->request->lang['return'] . $this->request->lang['success'],'result'=>$final];
	}

	protected function getSqlData( $connect, $data )
	{
		/*$sub_query = '( SELECT  id,finish_date,width,stops,work_time,stop_time,trimming,CASE WHEN total_len>total_len2 THEN CASE WHEN cutting_waste_qty>1 THEN  1 ELSE cutting_waste_qty END ELSE CASE WHEN cutting_waste_qty2>1 THEN  1 ELSE cutting_waste_qty2 END END AS shift_cutting_waste_qty,CASE WHEN total_len>total_len2 THEN CASE WHEN cutting_waste_qty>0 THEN  (cutting_waste_qty-1) ELSE cutting_waste_qty END ELSE CASE WHEN cutting_waste_qty2>0 THEN  (cutting_waste_qty2-1) ELSE cutting_waste_qty2 END END AS cutting_waste_qty,Convert(decimal(18,1),case when avg_speed > 300 then 180 else avg_speed end) as avg_speed,shift_code,CASE WHEN total_len>total_len2 THEN good_qty ELSE good_qty2 END AS good_qty,CASE WHEN total_len>total_len2 THEN bad_qty ELSE bad_qty2 END AS bad_qty,CASE WHEN total_len>total_len2 THEN paper_len ELSE paper_len2 END AS paper_len FROM Finish ) AS A ';
		$params = $this->getCondition($data);
		try {
			$result = Db::connect( $connect )
			->table($sub_query)
			->where($params['condition'])
			->field($params['date'] . ' as statis_date')
			->field($params['field'])
			->group($params['date'])
			->select();
		} catch ( \Exception $e) {
			$result = [];
		}
		return $result;*/
		$params = $this->getCondition($data);
		$sub_query = ' ( SELECT  id, ' . $params['date'] . ' AS statis_date ,width,stops,work_time,stop_time,trimming,CASE WHEN total_len>total_len2 THEN CASE WHEN cutting_waste_qty>1 THEN  1 ELSE cutting_waste_qty END ELSE CASE WHEN cutting_waste_qty2>1 THEN  1 ELSE cutting_waste_qty2 END END AS shift_cutting_waste_qty,CASE WHEN total_len>total_len2 THEN CASE WHEN cutting_waste_qty>0 THEN  (cutting_waste_qty-1) ELSE cutting_waste_qty END ELSE CASE WHEN cutting_waste_qty2>0 THEN  (cutting_waste_qty2-1) ELSE cutting_waste_qty2 END END AS cutting_waste_qty,Convert(decimal(18,1),case when avg_speed > 300 then 180 else avg_speed end) as avg_speed,shift_code,CASE WHEN total_len>total_len2 THEN good_qty ELSE good_qty2 END AS good_qty,CASE WHEN total_len>total_len2 THEN bad_qty ELSE bad_qty2 END AS bad_qty,CASE WHEN total_len>total_len2 THEN paper_len ELSE paper_len2 END AS paper_len FROM Finish WHERE ' . $params['condition'] . ') AS A ';
		$query = 'SELECT statis_date,' . $params['field'] . ' FROM ' . $sub_query . ' GROUP BY statis_date ORDER BY statis_date DESC';
		try {
			$result = Db::connect( $connect )
			->query($query);
		} catch ( \Exception $e) {
			$result = [];
		}
		return $result;
	}

	public function fetchPaperFinishList() 
	{
		$data = $this->request->post();
		$this->validate( $data, 'app\sg\validate\StatisValidate.paperFinish' );
		$strWhere = "";
		if( isset($data["companyName"]) && !empty($data["companyName"]) ) {
			$strWhere .=  " AND customer_name=''".$data['companyName']."''";
		}
		if( isset($data["className"]) && !empty($data["className"]) ) {
			$strWhere .=  " AND shift_code=''".$data['className']."''";
		}
		if( isset($data["width"]) && !empty($data["width"]) ) {
			$strWhere .=  " AND width=''".$data['width']."''";
		}


		if( isset($data["bdL"]) && !empty($data["bdL"]) ) {
			$strWhere .=  " AND paper_len=''".$data['bdL']."''";
		}
		if( isset($data["bdW"]) && !empty($data["bdW"]) ) {
			$strWhere .=  " AND paper_w=''".$data['bdW']."''";
		}
		if( isset($data["fluteType"]) && !empty($data["fluteType"]) ) {
			$strWhere .=  " AND flute_type=''".$data['width']."''";
		}
		if( isset($data["paperCode"]) && !empty($data["paperCode"]) ) {
			$strWhere .=  " AND paper_code''".$data['paperCode']."''";
		}

		/*$data['beginDate'] = '2024-11-01';
		$data['endDate'] = '2024-11-28';*/

		if( $data["orderSource"] == 2 ) {
			$strWhere .= " AND order_source = ''车间'' ";
		}
		if( $data["orderSource"] == 3 ) {
			$strWhere .= " AND order_source = ''ERP'' ";
		}

		$sql = "exec  p_get_finishreport  ".$data['dataType'].", '".$strWhere." AND finish_date between ''".$data['beginDate']." 00:00:00'' AND '' ".$data['endDate']." 23:59:59''','order by SUMType' ";

		$connect = util::getConnect( config('app.db_config')[$data["lineNum"]] );

		$countRow = [
			"SUMType"=> $this->request->lang['sum'],
			"cutting_qty"=> 0,
			"cutting_waste_qty"=> 0,
			"good_len"=> 0,
			"bad_len"=> 0,
			"prod_len"=> 0,
			"total_len"=> 0,
			"good_sqm"=> 0,
			"bad_sqm"=> 0,
			"prod_sqm"=> 0,
			"trim_sqm"=> 0,
			"total_sqm"=> 0,
			"bad_rate"=> 0,
			"trim_rate"=> 0,
			"work_time_str"=> 0,
			"stop_time_str"=> 0,
			"stops"=> 0
		];
		try {
			$list = Db::connect($connect)->query($sql)[0];
			if( count($list) > 0 ) {
				// 删除多余的时间行
				$filteredArray = array_filter($list, function($subArray) {
				    return count($subArray) >= 1;
				});
				$list = array_values($filteredArray);

				// 不保留小数
				$roundField = [
					"good_len",
					"bad_len",
					"prod_len",
					"total_len",

					"good_sqm",
					"bad_sqm",
					"prod_sqm",
					"trim_sqm",
					"total_sqm",
				];

				// 保留两位小数
				$roundField2 = [
					"bad_rate",
					"trim_rate"
				];

				foreach( $list as $key=> $value ) {
					foreach( $value as $idx=> $item ) {
						if( isset($countRow[$idx]) && !in_array($idx, ["work_time_str", "stop_time_str", "bad_rate", "trim_rate", "SUMType"]) ) {
							$countRow[$idx] += $item;
						}
						if( in_array($idx, $roundField) ) {
							$list[$key][$idx] = (int)round($item);
						}
						if( in_array($idx, $roundField2) ) {
							$list[$key][$idx] = round($item, 2);
						}

					}

					$countRow["work_time_str"] += $value["work_time"];
					$countRow["stop_time_str"] += $value["stop_time"];
				}
			}

			foreach( $countRow as $key=> $value ) {
				if( in_array($key, $roundField) ) {
					$countRow[$key] = (int)round($value);
				}
			}
			$countRow["bad_rate"] = round( $countRow["bad_sqm"] / $countRow["total_sqm"] * 100, 2 );
			$countRow["trim_rate"] = round( $countRow["trim_sqm"] / $countRow["total_sqm"] * 100, 2 );
			$countRow["avg_speed"] = ceil($countRow["good_len"] / ( $countRow["work_time_str"] + $countRow["stop_time_str"] ) * 600) / 10;
			$countRow["work_time_str"] = util::formatSeconds($countRow["work_time_str"]);
			$countRow["stop_time_str"] = util::formatSeconds($countRow["stop_time_str"]);
			array_push($list, $countRow);
			return ['errorCode'=>'00000','msg'=>$this->request->lang['return'] . $this->request->lang['success'], 'result'=> $list];
		} catch(\Exception $e) {
			
			throw new \app\common\exception\SgException(['msg'=> $this->request->lang['fail']]);
		}
		
	}


	public function fetchPaperFinishAnalysisData() 
	{
		$data = $this->request->post();
		$this->validate( $data, 'app\sg\validate\StatisValidate.paperFinishAnalysis' );

		$strWhere = "";
		if( isset($data["companyName"]) && !empty($data["companyName"]) ) {
			$strWhere .=  " AND customer_name=''".$data['companyName']."''";
		}
		if( isset($data["className"]) && !empty($data["className"]) ) {
			$strWhere .=  " AND shift_code=''".$data['className']."''";
		}
		if( isset($data["width"]) && !empty($data["width"]) ) {
			$strWhere .=  " AND width=''".$data['width']."''";
		}
		if( isset($data["bdL"]) && !empty($data["bdL"]) ) {
			$strWhere .=  " AND paper_len=''".$data['bdL']."''";
		}
		if( isset($data["bdW"]) && !empty($data["bdW"]) ) {
			$strWhere .=  " AND paper_w=''".$data['bdW']."''";
		}
		if( isset($data["fluteType"]) && !empty($data["fluteType"]) ) {
			$strWhere .=  " AND flute_type=''".$data['width']."''";
		}
		if( isset($data["paperCode"]) && !empty($data["paperCode"]) ) {
			$strWhere .=  " AND paper_code''".$data['paperCode']."''";
		}

		if( $data["orderSource"] == 2 ) {
			$strWhere .= " AND order_source = ''车间'' ";
		}
		if( $data["orderSource"] == 3 ) {
			$strWhere .= " AND order_source = ''ERP'' ";
		}

		/*$data['beginDate'] = '2024-11-01';
		$data['endDate'] = '2024-11-30';*/

		$sql = "exec p_get_finishanalysisreport 2, '".$strWhere." AND finish_date between ''".$data['beginDate']." 00:00:00'' AND ''".$data['endDate']." 23:59:59''', ''";

		$connect = util::getConnect( config('app.db_config')[$data["lineNum"]] );

		try {
			$list = Db::connect($connect)->query($sql);
			$timeRange = "";
			$resp = [
				"timeRange"=> "",
				"prodInfo"=> [],
				"machineList"=> [],
			];
			if( count($list) > 0 ) {
				// 生产信息汇总
				// 第一行
				$resp["prodInfo"][0]["col1_value"] = $list[0][0]["work_time"];
				$resp["prodInfo"][0]["col1_read"] = $this->request->lang["workTime"];
				$resp["prodInfo"][0]["col2_value"] = $list[0][0]["total_len"];
				$resp["prodInfo"][0]["col2_read"] = $this->request->lang["totalLen"];
				$resp["prodInfo"][0]["col3_value"] = $list[0][0]["total_sqm"];
				$resp["prodInfo"][0]["col3_read"] = $this->request->lang["totalSqm"];
				$resp["prodInfo"][0]["col4_value"] = $list[0][0]["total_wt"];
				$resp["prodInfo"][0]["col4_read"] = $this->request->lang["totalWT"];
				// 第二行
				$resp["prodInfo"][1]["col1_value"] = $list[0][0]["stop_time"];
				$resp["prodInfo"][1]["col1_read"] = $this->request->lang["stopTime"];
				$resp["prodInfo"][1]["col2_value"] = $list[0][0]["good_len"];
				$resp["prodInfo"][1]["col2_read"] = $this->request->lang["goodLen"];
				$resp["prodInfo"][1]["col3_value"] = $list[0][0]["good_sqm"];
				$resp["prodInfo"][1]["col3_read"] = $this->request->lang["goodSqm"];
				$resp["prodInfo"][1]["col4_value"] = $list[0][0]["good_wt"];
				$resp["prodInfo"][1]["col4_read"] = $this->request->lang["goodWT"];
				// 第三行
				$resp["prodInfo"][2]["col1_value"] = $list[0][0]["stops"];
				$resp["prodInfo"][2]["col1_read"] = $this->request->lang["stops"];
				$resp["prodInfo"][2]["col2_value"] = $list[0][0]["bad_len"];
				$resp["prodInfo"][2]["col2_read"] = $this->request->lang["badLen"];
				$resp["prodInfo"][2]["col3_value"] = $list[0][0]["bad_sqm"];
				$resp["prodInfo"][2]["col3_read"] = $this->request->lang["badSqm"];
				$resp["prodInfo"][2]["col4_value"] = $list[0][0]["bad_wt"];
				$resp["prodInfo"][2]["col4_read"] = $this->request->lang["badWT"];
				$resp["prodInfo"][2]["col5_value"] = sprintf("%.2f", $list[0][0]["bad_rate"]);
				$resp["prodInfo"][2]["col5_read"] = $this->request->lang["badRate"];
				// 第四行
				$resp["prodInfo"][3]["col1_value"] = $list[0][0]["avg_speed"];
				$resp["prodInfo"][3]["col1_read"] = $this->request->lang["avgSpeed"];
				$resp["prodInfo"][3]["col2_value"] = $list[0][0]["cutting_qty"];
				$resp["prodInfo"][3]["col2_read"] = $this->request->lang["cuttingQty"];
				$resp["prodInfo"][3]["col3_value"] = $list[0][0]["trim_sqm"];
				$resp["prodInfo"][3]["col3_read"] = $this->request->lang["trimSqm"];
				$resp["prodInfo"][3]["col4_value"] = $list[0][0]["trim_wt"];
				$resp["prodInfo"][3]["col4_read"] = $this->request->lang["trimWT"];
				$resp["prodInfo"][3]["col5_value"] = sprintf("%.2f", $list[0][0]["trim_rate"]);
				$resp["prodInfo"][3]["col5_read"] = $this->request->lang["trimRate"];
				// 第五行
				$resp["prodInfo"][4]["col1_value"] = $list[0][0]["avg_width"];
				$resp["prodInfo"][4]["col1_read"] = $this->request->lang["avgWidth"];
				$resp["prodInfo"][4]["col2_value"] = $list[0][0]["shift_cutting_waste_qty"];
				$resp["prodInfo"][4]["col2_read"] = $this->request->lang["shiftCuttingWasteQty"];
				$resp["prodInfo"][4]["col3_value"] = $list[0][0]["shift_cutting_waste_sqm"];
				$resp["prodInfo"][4]["col3_read"] = $this->request->lang["shiftCuttingWasteSqm"];
				$resp["prodInfo"][4]["col4_value"] = $list[0][0]["shift_cutting_waste_wt"];
				$resp["prodInfo"][4]["col4_read"] = $this->request->lang["shiftCuttingWasteWT"];
				$resp["prodInfo"][4]["col5_value"] = sprintf("%.2f", $list[0][0]["shift_cutting_waste_rate"]);
				$resp["prodInfo"][4]["col5_read"] = $this->request->lang["shiftCuttingWasteRate"];
				// 第六行
				$resp["prodInfo"][5]["col1_value"] = $list[0][0]["order_count"];
				$resp["prodInfo"][5]["col1_read"] = $this->request->lang["orderCount"];
				$resp["prodInfo"][5]["col2_value"] = $list[0][0]["cutting_waste_qty"];
				$resp["prodInfo"][5]["col2_read"] = $this->request->lang["cuttingWasteQty"];
				$resp["prodInfo"][5]["col3_value"] = $list[0][0]["cutting_waste_sqm"];
				$resp["prodInfo"][5]["col3_read"] = $this->request->lang["cuttingWasteSqm"];
				$resp["prodInfo"][5]["col4_value"] = $list[0][0]["cutting_waste_wt"];
				$resp["prodInfo"][5]["col4_read"] = $this->request->lang["cuttingWasteWT"];
				$resp["prodInfo"][5]["col5_value"] = sprintf("%.2f", $list[0][0]["cutting_waste_rate"]);
				$resp["prodInfo"][5]["col5_read"] = $this->request->lang["cuttingWasteRate"];
				// 第七行
				$resp["prodInfo"][6]["col1_value"] = $list[0][0]["avg_len"];
				$resp["prodInfo"][6]["col1_read"] = $this->request->lang["avgLen"];
				$resp["prodInfo"][6]["col2_value"] = util::countFluteTimes($list[0][0]["fluteStr"]);
				$resp["prodInfo"][6]["col2_read"] = $this->request->lang["fluteTimes"];
				$resp["prodInfo"][6]["col3_value"] = $list[0][0]["total_waste_sqm"];
				$resp["prodInfo"][6]["col3_read"] = $this->request->lang["totalWasteSqm"];
				$resp["prodInfo"][6]["col4_value"] = $list[0][0]["total_waste_wt"];
				$resp["prodInfo"][6]["col4_read"] = $this->request->lang["totalWasteWT"];
				$resp["prodInfo"][6]["col5_value"] = sprintf("%.2f", $list[0][0]["total_waste_rate"]);
				$resp["prodInfo"][6]["col5_read"] = $this->request->lang["totalWasteRate"];

				// 生产机台数据分析
				$resp["machineList"] = $list[1];

				$conutMachine =[
					"total_sqm"=> 0,
					"actual_sqm"=> 0,
					"waste_sqm"=> 0,
					"total_wt"=> 0,
					"actual_wt"=> 0,
					"waste_wt"=> 0,
				];

				foreach( $resp["machineList"] as $key=> $value ) {
					foreach( $value as $idx=> $item ) {
						if( isset($conutMachine[$idx]) ) {
							$conutMachine[$idx] += $item;
						}
					}
				}
				$conutMachine["layer"] = $this->request->lang['sum'];
				$conutMachine["waste_rate"] = $conutMachine["total_wt"] > 0 ? round($conutMachine["waste_wt"]/$conutMachine["total_wt"]*100, 2) : "0.00";
				array_push($resp["machineList"], $conutMachine);

				// 时间格式转换
				$startTime = new \DateTime($list[2][0]["startTime"]);
				$endTime = new \DateTime($list[3][0]["endTime"]);
				$resp["timeRange"] = $startTime->format('Y-m-d H:i:s'). "~" . $endTime->format('Y-m-d H:i:s');
			}
			return ['errorCode'=>'00000','msg'=>$this->request->lang['return'] . $this->request->lang['success'], 'result'=> $resp];
		} catch(\Exception $e) {
			var_dump($e->getMessage());
			throw new \app\common\exception\SgException(['msg'=> $this->request->lang['fail']]);
		}

	}



}
