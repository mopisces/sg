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
		$data = $this->request->post('user_name') . '/' . $this->request->post('user_pass');
		$info = [];
		switch ( $data ) {
			case config('sg_user0'):
				$info['root'] = 0; 
				break;
			case config('sg_user1'):
				$info['root'] = 1; 
				break;
			case config('sg_user2'):
				$info['root'] = 2; 
				break;
			default:
				throw new \app\common\exception\SgException(['msg'=>'用户名或密码错误']);
				break;
		}
		$info['data'] = $data;
		$access_token_info = array_merge(['iss'=>'jp-erp','iat'=>time(),'exp'=> time() + config('app.jwt_alive_time')],$info);
		$access_token  = JWT::encode($access_token_info,config('app.jwt_salt'));

		return ['errorCode'=>'00000','msg'=>'登录成功','result'=>[ 'access_token' => $access_token, 'root' => $info['root'] ]];
	}
}