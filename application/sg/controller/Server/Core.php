<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use think\Db;
use app\common\udp\AnalyzeData;
use util;

class Core extends Controller
{
    protected $socket;
    protected $config_index = NULL;

    public function index($config_index)
    {
        $this->config_index = $config_index;
        $config = config('db_config')[ $this->config_index ];
        $this->socket = new SocketIO($config['socketio']['port']);
        $this->socket->on('workerStart', function($socket)use($config){
            Timer::add(1,function()use($config){
                if( $config['DB_DATA'] ){
                    $data = $this->getDataFromDB() ? $this->getDataFromDB() : '';
                }else{
                    $data = $this->analyzeUDP();
                }
                $this->socket->emit('AnalyUdpData' . $this->config_index,$data );
            });
        });
        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }

    protected function analyzeUDP()
    {
        $result = AnalyzeData::analyzeUdp($this->config_index);
        return json_encode($result);
    }

    protected function getDataFromDB()
    {
        $config = config('db_config')[ $this->config_index ];
        $connect = util::getConnect($config);
        try {
            $data = Db::connect($connect)->table('proddata')->where('id',1)->value('data');
            //$data = substr(str_replace(" ", '', $data), 2);
            $data = str_replace(" ", '', $data);
            $result = AnalyzeData::analyzeUdp( $this->config_index, $this->byteTostr($data) );
            return json_encode($result);
        } catch ( \Exception $e) {
            return false;
        }
    }

    protected function byteTostr($hex)
    {
        $str = '';
        for( $i = 0; $i < strlen( $hex ) - 1; $i += 2 ){
            $str .= chr( hexdec( $hex[ $i ].$hex[ $i + 1 ] ) );
        }
        return $str;
    }
}


