<?php
namespace app\http\middleware;

use \Firebase\JWT\JWT; 
use think\facade\Config;
use think\Db;
use util;

class CheckSg
{
    public function handle($request, \Closure $next)
    {

    	if( NULL === $request->header('Authentication') ){
            throw new \app\common\exception\SgException(['msg'=>'Authentication error']);
        }
        $info = (array)JWT::decode($request->header('Authentication'),Config::get('app.jwt_salt'),["HS256"]);
        if( isset($info['langs']) ) {
            $request->lang = util::getLanguage($info['langs']);
        } else {
            $request->lang = util::getLanguage();
        }
        $result = Db::table('W_UserTable')
        ->where(['user'=>$info['user'],'pass'=>$info['pass'],'status'=>1])
        ->find();
        if( $result == NULL ){
            throw new \app\common\exception\AuthException();
        }
        $request->info = $result;
    	return $next($request);
    }
}
