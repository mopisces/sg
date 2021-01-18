<?php
namespace app\http\middleware\user;

use think\Db;

class CheckRoot
{
    public function handle($request, \Closure $next)
    {
        if( '2' == $request->info['root'] || !isset($request->info['root']) ){
            throw new \app\common\exception\SgException(['msg'=>'权限受限制']);
        }
    	return $next($request);
    }
}