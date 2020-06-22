<?php
namespace app\sg\validate;

use think\Validate;
use think\Db;
use think\facade\Request;

class LoginValidate extends Validate
{
	protected $rule = [
		'user_name' => 'require|max:5',
		'user_pass' => 'require|max:5'
	];
	
}