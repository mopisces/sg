<?php
namespace app\sg\validate;

use think\Validate;
use think\Db;
use think\facade\Request;

class AlterValidate extends Validate
{
	protected $rule = [
		'alter_config_index'  => ['require','regex'=>'/^[0-9]+$/','checkIndex'],
		'change_config_index' => ['require','regex'=>'/^[0-9]+$/','checkIndex'],
		'change_id'           => ['regex'=>'/^[1-9]+[0-9]*$/','max:5'],
		'change_value'        => ['require','regex'=>'/^[1-9]+[0-9]*$/','max:5'],
	];
	
	protected $scene = [
		'getConfig'   => ['alter_config_index'],
		'getRecord'   => ['alter_config_index'],
		'changeValue' => ['change_config_index','change_id','change_value'],
	];

	protected function checkIndex( $value,$rule,$data,$fieldName )
	{
		return isset(config('app.db_config')[$value]) ? true : $fieldName.'参数不正确';
	}
}