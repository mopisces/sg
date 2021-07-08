<?php
namespace app\sg\validate;

use think\Validate;

class StatisValidate extends Validate
{
	protected $rule = [ 
		'begin_date'  => 'require|dateFormat:Y-m-d|checkStatisDate',
		'end_date'    => 'require|dateFormat:Y-m-d|checkStatisDate',
		'class'       => 'require|in:A,B,C,D,ALL',
		'line'        => 'require|checkLine',
		'time_type'   => 'require|in:1,2,3',  //1按天 2按周 3按月
		'statis_type' => 'require|in:sumArea,sumLen,avgSpeed,sumLoss,sumStops'
	];

	protected $scene = [
		'getStatisData' => ['begin_date','end_date','class','line','time_type','statis_type']
	];

	protected function checkStatisDate( $value,$rule,$data,$fieldName )
	{
		$date_config = [ [ 'Name'=>'MaxTime' , 'Value'=>time() ], [ 'Name'=>'MinTime' , 'Value'=>strtotime('-1 year') ] ];
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
		if( $fieldName === 'end_date' ){
			if( strtotime($value) <  strtotime($data['begin_date']) ){
				return '结束日期小于开始日期' ;
			}
		}
		return true;
	}

	protected function checkLine( $value,$rule,$data,$fieldName )
	{
		if( $value === 'ALL' ) return true;
		if( preg_match_all("/^[0-9]{1}$/", $value) >= 1 && isset( config('app.db_config')[$value] ) ) return true;
		return '线别参数错误';
	}
}
