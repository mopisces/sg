<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;

class InsertDB extends Controller
{
    protected $socket;
    protected $config_index = 0;

    public function index()
    {
        $this->socket = new SocketIO(41000);
        $this->socket->on('workerStart', function($socket){
            Timer::add(1,function(){
                $result = Db::table('WebConfig')->where('Name','TestData')->update([
                    'Value' => time()
                ]);
                var_dump($result);
            });
        });
    }


}
