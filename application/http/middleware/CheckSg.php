<?php
namespace app\http\middleware;

use \Firebase\JWT\JWT; 
use think\facade\Config;
use think\Db;
class CheckSg
{
    public function handle($request, \Closure $next)
    {

    	if( NULL === $request->header('Authentication') ){
            throw new \app\common\exception\SgException(['msg'=>'Authentication参数未定义']);
        }
        /*var_dump($request->header('Authentication'));
        var_dump(Config::get('app.jwt_salt'));
        die;*/
        $info = (array)JWT::decode($request->header('Authentication'),Config::get('app.jwt_salt'),["HS256"]);
        if( !isset($info['root']) || empty($info['root']) ){
            throw new \app\common\exception\SgException(['msg'=>'权限受限制']);
        }
        $factory_id = Db::table('WebConfig')->where('Name','FactoryId')->value('Value');
    	Config::set('app.factory_id',$factory_id);
    	return $next($request);
    }
}
