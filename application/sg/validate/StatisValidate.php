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
		'statis_type' => 'require|in:sumArea,sumLen,avgSpeed,sumLoss,sumStops',
		'dataType'=> 'require|in:4,5,6,7,9',
		'companyName'=> 'max:100',
		'className'=> 'max:10',
		'width'=> ['max:10', 'regex'=>'/^[0-9]$/'],
		'bdL'=> ['max:10', 'regex'=>'/^[0-9]$/'],
		'bdW'=> ['max:10', 'regex'=>'/^[0-9]$/'],
		'fluteType'=> 'max:100',
		'paperCode'=> 'max:100',
		'beginDate'=> 'require|dateFormat:Y-m-d',
		'endDate'=> 'require|dateFormat:Y-m-d',
		'lineNum'=> ['require', 'regex'=>'/^[0-9]{1,3}$/', 'checkIndex', "checkLineNew"],
		'orderSource'=> ["require", "in:1,2,3"], //1->全部 2->车间加单 3->erp
	];

	protected $scene = [
		'getStatisData' => ['begin_date','end_date','class','line','time_type','statis_type'],
		"paperFinish"=> [
			"dataType", 
			"companyName",
			"className",
			"width",
			"bdL",
			"bdW",
			"fluteType",
			"paperCode",
			"beginDate",
			"endDate",
			"lineNum",
			"orderSource"
		],
		"paperFinishAnalysis"=> [
			"companyName",
			"className",
			"width",
			"bdL",
			"bdW",
			"fluteType",
			"paperCode",
			"beginDate",
			"endDate",
			"lineNum",
			"orderSource"
		]
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

	protected function checkIndex( $value,$rule,$data,$fieldName )
	{
		return isset(config('app.db_config')[$value]) ? true : $fieldName.'参数不正确';
	}

	protected function checkLineNew( $value,$rule,$data,$fieldName ) 
	{
		if( isset(config('app.db_config')[$value]) && config('app.db_config')[$value]["isnew"] == 1 ) {
			return true;
		}
		return "生产线不支持";
	}
}
