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
}
