<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use \Firebase\JWT\JWT;

class LoginController extends Controller
{
	protected $failException = true;

	public function login()
	{
		$this->validate( $this->request->post(),'app\sg\validate\LoginValidate' );
		$info = Db::table('W_UserTable')
		->where([
			'user'   => $this->request->post('user_name'),
			'pass'   => $this->request->post('user_pass'),
		])
		->find();
		if( $info == NULL ){
			throw new \app\common\exception\SgException(['msg'=>'用户名或密码错误']);
		}
		if( $info['status'] == '0' ){
			throw new \app\common\exception\SgException(['msg'=>'账号暂未启用']);
		}
		$access_token_info = array_merge(['iss'=>'jp-erp','iat'=>time(),'exp'=> time() + config('app.jwt_alive_time')],$info);
		$access_token  = JWT::encode($access_token_info,config('app.jwt_salt'));
		return ['errorCode'=>'00000','msg'=>'登录成功','result'=>[ 'access_token' => $access_token, 'root' => $info['root'] ]];
	}

	public function getFactoryName()
	{
		return ['errorCode'=>'00000','msg'=>'返回成功','result'=>config('factory_name')];
	}
}