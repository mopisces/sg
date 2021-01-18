<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use util;

class SelectController extends Controller
{
	protected $failException = true;
	protected $scddPageSize = 3;
	protected $wgddPageSize = 3;
	protected $openWeightBl = 0;

	public function getConfig()
	{
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>config('db_config'), 'weight' => $this->openWeightBl];
	}

	public function getBl()
	{
		$this->validate( $this->request->post(),'app\sg\validate\SelectValidate.getBl' );
		if( config('app.db_config')[$this->request->post('bl_config_index')]['isnew'] ){
			$table_name = 'MyOrder';
		}else{
			$table_name = 1 === config('app.db_config')[$this->request->post('bl_config_index')]['updown'] ? 'MyOrder' : 'bc';
		}
		$connect = util::getConnect( config('app.db_config')[$this->request->post('bl_config_index')] );
		try {
			$result = Db::connect($connect)
			->query('exec P_GetPrePaperTotal '. $table_name . ',\'\'' )[0];
		} catch ( \Exception $e ) {
			throw new \app\common\exception\SgException(['msg'=>'备料模块数据获取失败']);
			
		}
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	public function getBlms()
	{
		$this->validate( $this->request->post(),'app\sg\validate\SelectValidate.getBlms' );
		$config = config('app.db_config')[$this->request->post('blms_config_index')];
		$connect = util::getConnect( $config );
		if( '1' == $this->request->post('blms_active_type') && $this->openWeightBl == 1 ){
			$this->getBlmsWeight($connect);
		}
		$filter_name = 1 === $config['isnew'] ? 'paper_code' : 'paper';
		try {
			$data = Db::connect($connect)
			->table('view_myorder')
			->whereExp($filter_name,'IS NOT NULL')
			->field('sn,' . $filter_name . ' as paper_code,width as paper_width,(paper_len * cutting_qty / 1000.0) as paper_length')
			->order('sn')
			->select();
			$flute = Db::connect($connect)->query('exec P_GetFlute')[0];
		} catch ( \Exception $e ) {
			throw new \app\common\exception\SgException(['msg'=>'备料米数模块数据获取失败']);
		}
		$ceng_info = ['糊机备纸','SF1芯纸','SF1面纸','SF2芯纸','SF2面纸','SF3芯纸','SF3面纸'];
		if( 1 === $config['updown'] ){
			$result = [];
			foreach ($data as $key => $value) {
				$result[$value['sn']][] = $value;
			}
			foreach ($result as $key_res => $value_res) {
				$paper_length = $value_res[0]['paper_length'];
				if( isset( $value_res[1]['paper_length'] ) ){
					$paper_length = max($value_res[0]['paper_length'],$value_res[1]['paper_length']);
				}
				$result[$key_res] = [
					'paper_code'   => $value_res[0]['paper_code'],
                    'paper_width'  => $value_res[0]['paper_width'],
                    'paper_length' => $paper_length
				];
			}
			$result = array_values($result);
		}else{
			$result = $data;
			foreach ($result as $key_data => $value_data) {
				$repeat = count( $ceng_info ) - strlen($result[$key_data]['paper_code']);
				if($repeat > 0){
					$result[$key_data]['paper_code'] .= str_repeat(str_repeat($config['paperCodeSpaceChar'],$config['paperCodeNumber']),$repeat);
				}
			}
		}
		$new_data    = [];
		$array_count = [];
		foreach ($ceng_info as $ceng_info_key => $ceng_info_value) {
			$new_data_son     = [];
			$paper_code_flag  = '';
			$paper_width_flag = '';
			foreach ($result as $result_key => $result_value) {
				$paper_code = substr( $result[ $result_key ]['paper_code'], $ceng_info_key * $config['paperCodeNumber'], $config['paperCodeNumber'] );
				if( !$paper_code || $paper_code === str_repeat( $config['paperCodeSpaceChar'], $config['paperCodeNumber'] ) ){
					continue;
				}
				if( $paper_code === $paper_code_flag && $result[ $result_key ]['paper_width'] === $paper_width_flag ){
					$new_data_son[ count($new_data_son) - 1 ]['PaperLength'] += (float)$result[$result_key]['paper_length'];
				}else{
					$paper_code_flag  = $paper_code;
					$paper_width_flag = $result[$result_key]['paper_width'];
					$new_data_son[]   = ['PaperCode'=>$paper_code,'PaperWidth'=>$result[$result_key]['paper_width'],'PaperLength'=>(float)$result[$result_key]['paper_length']];
				}

			}
			$new_data[$ceng_info[$ceng_info_key]] = $new_data_son;
			$array_count[] = count($new_data_son);
		}
		foreach ($new_data as $new_data_key => $new_data_value) {
			if( 'SF1芯纸' === $new_data_key ){
				$flute_rate = $flute[0]['FluteRate1'];
			}elseif( 'SF2芯纸' === $new_data_key ){
				$flute_rate = $flute[0]['FluteRate2'];
			}elseif( 'SF3芯纸' === $new_data_key ){
				$flute_rate = $flute[0]['FluteRate3'];
			}else{
				$flute_rate = 1;
			}
			foreach ($new_data_value as $key => $value) {
				$new_data[$new_data_key][$key]['PaperLength'] = round($value['PaperLength'] * $flute_rate,0);
			}
		}
		$table_data = [];
		for ( $i = 0; $i < max($array_count); $i++ ) { 
			$table_data_son = [];
			for ( $k = 0; $k < count($ceng_info); $k++ ) { 
				if( isset($new_data[ $ceng_info[ $k ] ][ $i ]) ){
					$table_data_son[ $ceng_info[ $k ] ] = $new_data[ $ceng_info[ $k ] ][ $i ]['PaperCode'] . '*' . $new_data[ $ceng_info[ $k ] ][ $i ]['PaperWidth'] . '=' . $new_data[ $ceng_info[ $k ] ][ $i ]['PaperLength'];
				}
			}
			$table_data[ $i ] = $table_data_son;
		}
		
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$table_data];
	}

	protected function getBlmsWeight( $connect )
	{
		try {
			$data = Db::connect($connect)->exec('exec p_GetPaperWetOrLenth ');
		} catch ( \Exception $e) {
			throw new \app\common\exception\SgException(['msg' => '按克重获取数据失败']);
		}
		if( NULL === $data ){
			throw new \app\common\exception\SgException(['msg' => '按克重暂无数据']);
		}
		$result = [];
		foreach ($data as $key => $value) {
			$result[$key]['糊机备纸'] = $value['layer1'];
          	$result[$key]['SF1芯纸']  = $value['layer2'];
           	$result[$key]['SF1面纸']  = $value['layer3'];
           	$result[$key]['SF2芯纸']  = $value['layer4'];
           	$result[$key]['SF2面纸']  = $value['layer5'];
           	$result[$key]['SF3芯纸']  = $value['layer6'];
           	$result[$key]['SF3面纸']  = $value['layer7'];
		}
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	public function getScdd()
	{
		$this->validate( $this->request->post(),'app\sg\validate\SelectValidate.getScdd' );
		$condition = $this->getConditionScdd($this->request->post());
		$connect = util::getConnect( config('app.db_config')[$this->request->post('scdd_config_index')] );
		$count = Db::connect($connect)->table('view_myorder')->where($condition)->count();
		$size = 1 === config('app.db_config')[$this->request->post('scdd_config_index')]['updown'] ? 2:1;
		$data = [];
		$info = [];
		if( 0 != $count ){
			$data = Db::connect($connect)
			->table('view_myorder')
			->where($condition)
			->order('sn,tag','desc')
			->page($this->request->post('cur_page'),$this->scddPageSize * $size )
			->select();
			foreach ($data as $key => $value) {
				if( isset($value['pre_finishtime']) && $value['pre_finishtime'] ){
					$data[$key]['pre_finishtime'] = date('Y-m-d H:i:s',strtotime($value['pre_finishtime']));
				}
			}
			if( 2 === $size ){
				foreach ($data as $key => $value) {
					$info[$value['sn']][] = $value;
				}
				$info = array_values($info);
				foreach ($info as $info_key => $info_value) {
					$result[] = $info_value[1];
					$result[] = $info_value[0];
				}
			}else{
				$result = $data;
			}
		}
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	protected function getConditionScdd( $data )
	{
		$condition = [];
		if( isset($data['scdd_sn']) && !empty($data['scdd_sn']) ){
			$condition[] = ['sn','=',$data['scdd_sn']];
		}
		if( isset($data['scdd_order_number']) && !empty($data['scdd_order_number']) ){
			$condition[] = ['order_number','like','%' . $data['scdd_order_number'] . '%'];
		}
		if('2' !== $this->request->info['root']){
			if( isset($data['scdd_company_name']) && !empty($data['scdd_company_name']) ){
				$condition[] = ['company_name','like','%' . $data['scdd_company_name'] . '%'];
			}
		}
		$field = 1 === config('app.db_config')[$data['scdd_config_index']]['isnew'] ? 'paper_code' : 'paper';
		if( isset($data['scdd_paper_code']) && !empty($data['scdd_paper_code']) ){
			$condition[] = [$field,'like','%' . $data['scdd_paper_code'] . '%']; 
		}
		/*if( isset($data['scdd_paper_code']) && !empty($data['scdd_paper_code']) ){
			$condition[] = ['paper_code','like','%' . $data['scdd_paper_code'] . '%']; 
		}*/

		if( isset($data['scdd_flute_type']) && !empty($data['scdd_flute_type']) ){
			$condition[] = ['flute_type','=',$data['scdd_flute_type']];
		}
		if( isset($data['scdd_width']) && !empty($data['scdd_width']) ){
			$condition[] = ['width','=',$data['scdd_width']];
		}
		return $condition;
	}

	public function getWgddConfig()
	{
		$result = [ 
			'minDate'   => date('Y-m-d',strtotime('-1 year')) ,
			'maxDate'   => date('Y-m-d',strtotime('now')),
			'beginDate' => date('Y-m-d',strtotime('now')),
			'endDate'   => date('Y-m-d',strtotime('now')),
		];
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	public function getWgdd()
	{
		$this->validate( $this->request->post(),'app\sg\validate\SelectValidate.getWgdd' );
		$condition = $this->getConditionWgdd($this->request->post());
		$connect = util::getConnect( config('app.db_config')[$this->request->post('wgdd_config_index')] );
		$size = 1 === config('app.db_config')[$this->request->post('wgdd_config_index')]['updown'] ? 2:1;
		$data = Db::connect($connect)
		->table('view_finish')
		->where($condition)
		->order('id desc,tag desc')
		->page( $this->request->post('cur_page'), $this->wgddPageSize * $size )
		->select();
		if( NULL === $data || [] === $data ){
			throw new \app\common\exception\SgException(['msg'=>'完工订单模块数据获取失败']);
		}
		$data = array_map(function($item){
			if( $item['start_time'] ){
				$item['start_time'] = date('Y-m-d H:i:s',strtotime($item['start_time']));
			}
			if( $item['finish_date'] ){
				$item['finish_date'] = date('Y-m-d H:i:s',strtotime($item['finish_date']));
			}
			return $item;
		},$data);
		if( config('app.db_config')[$this->request->post('wgdd_config_index')]['updown'] ){
			$result = [];
			foreach ($data as $key => $value) {
				$result[$value['id']][] = $value;
			}
			$result = array_values($result);
			foreach ($result as $res_key => $res_value) {
				$return[] = $res_value[0];
				$return[] = $res_value[1];
			}
		}else{
			$return = $data;
		}
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$return ];
	}

	protected function 	getConditionWgdd( $data )
	{
		$condition = [];
		if( isset($data['wgdd_sn']) && !empty($data['wgdd_sn']) ){
			$condition[] = ['sn','=',$data['wgdd_sn']];
		}
		if( isset($data['wgdd_order_number']) && !empty($data['wgdd_order_number']) ){
			$condition[] = ['order_number','like','%' . $data['wgdd_order_number'] . '%'];
		}
		if('2' !== $this->request->info['root']){
			if( isset($data['wgdd_company_name']) && !empty($data['wgdd_company_name']) ){
				$condition[] = ['company_name','like','%' . $data['wgdd_company_name'] . '%'];
			}
		}
		$field = 1 === config('app.db_config')[$data['wgdd_config_index']]['isnew'] ? 'paper_code' : 'paper';
		if( isset($data['wgdd_paper_code']) && !empty($data['wgdd_paper_code']) ){
			$condition[] = [$field,'like','%' . $data['wgdd_paper_code'] . '%']; 
		}
		/*if( isset($data['wgdd_paper_code']) && !empty($data['wgdd_paper_code']) ){
			$condition[] = ['paper_code','like','%' . $data['wgdd_paper_code'] . '%']; 
		}*/

		if( isset($data['wgdd_flute_type']) && !empty($data['wgdd_flute_type']) ){
			$condition[] = ['flute_type','=',$data['wgdd_flute_type']];
		}
		if( isset($data['wgdd_width']) && !empty($data['wgdd_width']) ){
			$condition[] = ['width','=',$data['wgdd_width']];
		}
		if( isset($data['wgdd_begin_time']) && !empty($data['wgdd_begin_time']) && isset($data['wgdd_end_time']) && !empty($data['wgdd_end_time']) ){
			$condition[] = ['finish_date','between',[$data['wgdd_begin_time'],$data['wgdd_end_time']]];
		}elseif( isset($data['wgdd_begin_time']) && !empty($data['wgdd_begin_time']) ){
			$condition[] = ['finish_date','>=',$data['wgdd_begin_time'] ];
		}elseif( isset($data['wgdd_end_time']) && !empty($data['wgdd_end_time']) ){
			$condition[] = ['finish_date','<=',$data['wgdd_end_time'] ];
		}
		return $condition;
	}

}