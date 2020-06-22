<?php
namespace app\sg\validate;

use think\Validate;

class MonitorValidate extends Validate
{
	protected $rule = [ 
		'db_config_index' => ['require','regex'=>'/^[0-9]$/','checkIndex']
	];

	protected function checkIndex( $value,$rule,$data,$fieldName )
	{
		return isset(config('app.db_config')[$value]) ? true : $fieldName.'参数不正确';
	}
}
