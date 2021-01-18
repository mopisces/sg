<?php
namespace app\common\behavior;
 
use think\Request;
class CoreRun
{
    protected $domains = [ '*' ];

    public function run(Request $request)
    {
        foreach ($this->domains as $key => $value) {
            header('Access-Control-Allow-Origin:'.$value); 
        }
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS');  
        header('Access-Control-Allow-Headers:Authentication,content-type,session');
        if( $request->isOptions() ) {
            exit();
        }
    }
}