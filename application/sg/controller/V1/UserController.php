<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use util;

class UserController extends Controller
{
	protected $failException = true;

	protected $middleware    = [
		'app\http\middleware\user\CheckRoot' => [
			'only' => ['fetchList']
		]
	];

	public function fetchList()
	{
		$result = Db::table('W_UserTable')
		->where(['flag' => $this->request->info['root'],'status' => 1])
		->where('user', '<>', $this->request->info['user'])
		->field('id,user,pass')
		->select();
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>$result];
	}

	public function doEdit()
	{
		$this->validate( $this->request->post(),'app\sg\validate\UserValidate.edit' );
		if( $this->request->post('is_edit') == 0 ){
			$result = Db::table('W_UserTable')
			->data([
				'user'   => $this->request->post('user'),
				'pass'   => $this->request->post('pass'),
				'status' => 1,
				'root'   => 2,
				'flag'   => $this->request->info['root']
			])
			->insert();
			
		}
		if( $this->request->post('is_edit') == 1 ){
			$result = Db::table('W_UserTable')
			->where(['id'=>$this->request->post('id')])
			->update(['user'=>$this->request->post('user'),'pass'=>$this->request->post('pass')]);
		}
		if( $result ){
			return ['errorCode'=>'00000','msg'=>'操作成功！','result'=>NULL];
		}
	}

	public function doStatus()
	{
		$this->validate( $this->request->post(),'app\sg\validate\UserValidate.doStatus' );
		$result = Db::table('W_UserTable')
		->where([
			'id'=>$this->request->post('id'),
			'flag' => $this->request->info['root']
		])
		->update(['status' => $this->request->post('status')]);
		if( $result ){
			return ['errorCode'=>'00000','msg'=>'操作成功！','result'=>NULL];
		}
	}
}