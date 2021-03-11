<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use app\common\udp\AnalyzeData;

class Two extends Controller
{
    protected $socket;
    protected $config_index = 1;
    
    public function index()
    {
        $config = config('db_config')[ $this->config_index ];
        $this->socket = new SocketIO($config['socketio']['port']);
        $this->socket->on('workerStart', function($socket){
            Timer::add(2,function(){
                $buf = $this->analyzeUDP();
                $buf = json_encode($buf);
                $this->socket->emit('AnalyUdpData' . $this->config_index,$buf );
            });
        });
        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }

    protected function analyzeUDP()
	{
		return AnalyzeData::analyzeUdp($this->config_index);
	}

}
