<?php
namespace app\sg\controller\v1;

use think\Controller;
use util;

class HideController extends Controller
{
	protected $failException = true;

	public function detect()
	{
		$config = config('db_config')
		foreach ($config as $key => $value) {
			$socket = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
            socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,['sec' => 1,'usec' => 0]);
            socket_bind($socket,$value['socket_bind']['address'],$value['socket_bind']['port']);
            socket_recvfrom($socket,$buf,2048,0,$from,$port);
            socket_close($socket);
            $buf = unpack('C*',$buf);
            $result[] = [
                'flag'   => $value['DB_FLAG'],
                'status' => $buf?1:0,
            ];
		}
		return ['errorCode'=>'00000','msg'=>null,'result'=>$result];
	}
}