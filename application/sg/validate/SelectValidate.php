<?php
namespace app\sg\validate;

use think\Validate;
use think\Db;

class SelectValidate extends Validate
{
	protected $rule = [
		'cur_page'          => ['require','regex'=>'/^[1-9]\d*/'],

		'bl_config_index'   => ['require','regex'=>'/^[0-9]{1,3}$/','checkIndex'],
		'blms_config_index' => ['require','regex'=>'/^[0-9]{1,3}$/','checkIndex'],
		'blms_active_type'  => 'require|in:0,1|checkUseful', //0 按长度 1 按重量
		
		'scdd_sn'           => 'max:20|number',
		'scdd_order_number' => 'max:20|alphaNum',
		'scdd_company_name' => 'max:20|chsDash',
		'scdd_paper_code'   => 'max:20|chsAlphaNum',
		'scdd_flute_type'   => 'max:10|chsAlphaNum',
		'scdd_width'        => ['regex'=>'/^[0-9]+([.]{1}[0-9]+){0,1}$/'],
		'scdd_config_index' => ['require','regex'=>'/^[0-9]{1,3}$/','checkIndex'],

		'wgdd_sn'           => 'max:20|number',
		'wgdd_order_number' => 'max:20|alphaNum',
		'wgdd_company_name' => 'max:20|chsDash',
		'wgdd_paper_code'   => 'max:20|chsAlphaNum',
		'wgdd_flute_type'   => 'max:10|chsAlphaNum',
		'wgdd_width'        => ['regex'=>'/^[0-9]+([.]{1}[0-9]+){0,1}$/'],
		'wgdd_config_index' => ['require','regex'=>'/^[0-9]{1,3}$/','checkIndex'],
		'wgdd_begin_time'   => 'require|dateFormat:Y-m-d|checkWgddDate',
		'wgdd_end_time'     => 'require|dateFormat:Y-m-d|checkWgddDate',
	];
	
	protected $scene = [
		'getBl'   => ['bl_config_index'],
		'getBlms' => ['blms_config_index','blms_active_type'],
		'getScdd' => ['scdd_sn','scdd_order_number','scdd_company_name','scdd_paper_code','scdd_flute_type','scdd_width','scdd_config_index','cur_page'],
		'getWgdd' => ['wgdd_sn','wgdd_order_number','wgdd_company_name','wgdd_paper_code','wgdd_flute_type','wgdd_width','wgdd_config_index','wgdd_begin_time','wgdd_end_time','cur_page'],
	];

	protected function checkIndex( $value,$rule,$data,$fieldName )
	{
		return isset(config('app.db_config')[$value]) ? true : $fieldName.'参数不正确';
	}

	protected function checkWgddDate( $value,$rule,$data,$fieldName )
	{
		$date_config = [ [ 'Name'=>'MaxTime' , 'Value'=>strtotime('+1 day') ], [ 'Name'=>'MinTime' , 'Value'=>strtotime('-1 year') ] ];
		foreach ($date_config as $k => $v) {
			if( $v['Name'] === 'MaxTime' ){
				if( strtotime($value) > $date_config[$k]['Value'] ){
					return $fieldName . '参数超过最大时间' . date('Y-m-d',$date_config[$k]['Value']);
				}
			}
			if( $v['Name'] === 'MinTime' ){
				if( strtotime($value) < $date_config[$k]['Value']  - 3600*24 ){
					return $fieldName . '参数小于等于最小时间' . date('Y-m-d',$date_config[$k]['Value']);
				}
			}
		}
		if( $fieldName === 'wgdd_end_time' ){
			if( strtotime($value) <  strtotime($data['wgdd_begin_time']) ){
				return '结束日期小于开始日期' ;
			}
		}
		return true;
	}

	protected function checkUseful( $value,$rule,$data,$fieldName )
	{
		if( '1' == $value ){
			return config('app.db_config')[ $data['blms_config_index'] ]['isnew'] ? true : '老生管不支持按克重查询';
		}
		return true;
	}
}