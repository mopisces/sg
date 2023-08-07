<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
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
                var_dump($data);
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
        //$connect = util::getConnect($config);
        try {
           /* $conn = sqlsrv_connect('.\MWJ',[
                'Database'=>'cimdbv4',
                'UID' => 'sa',
                'PWD' => 'karry',
                'CharacterSet' => 'UTF-8'
            ]);*/
            $conn = sqlsrv_connect($config['DB_HOST'],[
                'Database'     => $config['DB_NAME'],
                'UID'          => $config['DB_USER'],
                'PWD'          => $config['DB_PWD'],
                'CharacterSet' => 'UTF-8'
            ]);
            if( $conn === false ){
                return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check line status']);;
            }
            $stmt = sqlsrv_query( $conn, 'SELECT data FROM proddata WHERE id = 1' );
            if( $stmt === false ){
                return json_encode(['ret'=>0,'data'=>NULL,'msg'=>'check table proddata']);;
            }
            $data = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC );
            //$data = substr(str_replace(" ", '', $data['data']), 2);
            $data = str_replace(" ", '', $data['data']);
            //$data = Db::connect($connect)->table('proddata')->where('id',1)->value('data');
            //$data = substr(str_replace(" ", '', $data), 2);
            //$data = str_replace(" ", '', $data);
            $result = AnalyzeData::analyzeUdp( $this->config_index, $this->byteTostr($data) );
            //sqlsrv_close( $conn );
            return json_encode($result);
        } catch ( \Exception $e) {
            return json_encode(['ret'=>0,'data'=>NULL, 'msg'=>$e->getMessage()]);;
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


