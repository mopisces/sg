<?php
namespace app\sg\validate;

use think\Validate;
use think\Db;
use think\facade\Request;

class UserValidate extends Validate
{
	protected $rule = [
		'id'      => ['requireIf:is_edit,1','checkId'],
		'is_edit' => ['require','in:0,1','CheckEdit'],
		'user'    => ['require'],
		'pass'    => ['require'],
		'status'  => ['require','in:0,1']
	];

	protected $scene = [
		'edit'     => ['id','user','pass','is_edit'],
		'doStatus' => ['id','status'],
	];

	protected function checkId( $value,$rule,$data,$fieldName )
	{
		$result = Db::table('W_UserTable')
		->where(['id'=>$value,'flag'=>Request::param('info')['root']])
		->find();
		return $result == NULL ? 'id不存在或者权限受限制' : TRUE;
	}

	protected function CheckEdit( $value,$rule,$data,$fieldName )
	{
		if( $value == '0' ){
			$result = Db::table('W_UserTable')
			->where(['user' => $data['user']])
			->find();
			return $result == NULL ? TRUE : '该账号已存在';
		}
		return TRUE;
	}
}