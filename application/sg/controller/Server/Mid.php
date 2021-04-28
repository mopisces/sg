<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use think\Db;

class Mid extends Controller
{
    protected $socket;
    protected $config_index = NULL;

    public function index()
    {
        try {
            $socket = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
            socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,['sec' => 1,'usec' => 0]);
            socket_connect($socket, '222.92.208.154', '42000');
            $data = socket_read($socket, 4096);
            var_dump($data);
           /* socket_bind($socket, '192.168.1.61', 5000);
            socket_recvfrom($socket,$buf,2048,0,$from,$port);
            socket_close($socket);
            $buf = unpack('C*',$buf);*/
        } catch ( \Exception $e) {
            var_dump($e->getMessage()); 
        }
    }

}


