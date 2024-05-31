<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use \Firebase\JWT\JWT;
use util;
use think\facade\Request;

class LoginController extends Controller
{
	protected $failException = true;

	public function login()
	{
		$data = $this->request->post();
		$this->validate( $data, 'app\sg\validate\LoginValidate' );
		$info = Db::table('W_UserTable')
		->where([
			'user'=> $data['user_name'],
			'pass'=> $data['user_pass'],
		])
		->find();
		if( isset($data['langs']) ) {
            $lang = util::getLanguage($data['langs']);
        } else {
            $lang = util::getLanguage();
        }
		if( $info == NULL ){
			throw new \app\common\exception\SgException(['msg'=> $lang['loginErr'] ]);
		}
		if( $info['status'] == '0' ){
			throw new \app\common\exception\SgException(['msg'=> $lang['accAliveErr']]);
		}
		$info['langs'] = $this->request->post('langs');
		$access_token_info = array_merge([
			'iss'=>'jp-erp',
			'iat'=>time(),
			'exp'=> time() + config('app.jwt_alive_time')
		],$info);
		$access_token  = JWT::encode($access_token_info,config('app.jwt_salt'));
		return ['errorCode'=>'00000','msg'=> $lang['login'].$lang['success'],'result'=>[ 'access_token' => $access_token, 'root' => $info['root'] ]];
	}

	public function getFactoryName()
	{
		$acceptLanguage = Request::header('accept-language');
		$languages = explode(',', $acceptLanguage);  
    	$primaryLanguage = "";  
   		foreach ($languages as $lang) {
   			if (preg_match('/^([a-z]{1,8}(-[a-z]{1,8})*)(\s*;\s*q\s*=\s*(\d{1}(\.\d+)?))?$/i', $lang, $matches)) {
   				list(, $language, $priorityPart) = $matches;
   			}
   		}
   		if( $language == 'zh-CN' ) {
   			$lang = util::getLanguage('zh');
   		} else {
   			$lang = util::getLanguage('en');
   		}
		return ['errorCode'=>'00000','msg'=> $lang['return'].$lang['success'],'result'=>config('factory_name')];
	}
}