<?php
namespace app\sg\validate;

use think\Validate;
use think\Db;
use think\facade\Request;

class LoginValidate extends Validate
{
	protected $rule = [
		'user_name' => 'require',
		'user_pass' => 'require'
	];
	
}