<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use think\Db;
use app\common\udp\AnalyzeData;
use util;

class Mid extends Controller
{
    protected $worker;

    public function index( $config_index = 0 )
    {
        $this->config_index = $config_index;
        $config = config('db_config')[ $this->config_index ];

        $worker = new Worker('websocket://0.0.0.0:50000');

        $this->socket = new SocketIO($config['socketio']['port']);

        $this->socket->on('workerStart', function($socket)use($config, $worker){
            Timer::add(1,function()use($config, $worker){
                $socket = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
                socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,['sec' => 1,'usec' => 0]);
                socket_bind($socket, $config['socket_bind']['address'], $config['socket_bind']['port']);
                socket_recvfrom($socket,$buf,2048,0,$from,$port);
                socket_close($socket);
                $buf = unpack('C*',$buf);
                foreach($worker->connections as $connection){
                    $connection->send($buf);
                }
                $this->socket->emit('AnalyUdpData' . $this->config_index, $buf );
            });
        });

        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });

        Worker::runAll();
    }

}


