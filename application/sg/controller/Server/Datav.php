<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use think\Db;

class Datav extends Controller
{

    public $factory_id;

    public function __construct()
    {
        parent::__construct();
        $this->factory_id = Db::table('WebConfig')->where('Name','FactoryId')->value('Value');
    }

    public function index()
    {
        $io = new SocketIO(8888);
        $io->on('workerStart', function($socket)use($io){
            $sub_fact = Db::table('SubFactory')->where('FactoryId',$this->factory_id)->select();
            if(!empty($sub_fact)){
                foreach ($sub_fact as $key => $value) {
                    $result = $this->getTableData($value['SubFacId']);
                    Timer::add(5,function()use($io,$result,$value){
                        $io->emit( $value['SubFacId'] . 'LeftMsg', $result['un_car']);
                    });
                    Timer::add(5,function()use($io,$result,$value){
                        $io->emit( $value['SubFacId'] . 'RightMsg', $result['un_sign']);
                    });
                }
            }else{
                $result = $this->getTableData();
                Timer::add(30,function()use($io,$result){
                    $io->emit( $this->factory_id . 'LeftMsg', $result['un_car']);
                });
                Timer::add(600,function()use($io,$result){
                    $io->emit( $this->factory_id . 'RightMsg', $result['un_sign']);
                });
            }
        });
        /*$io->on('workerStart', function($socket)use($io){
            $sub_factory = Db::table('SubFactory')->select();
            Timer::add(1,function()use($io,$sub_factory){
                $io->emit('factoryInfo',$sub_factory);
            });
        });*/
        $io->on('connection',function($socket)use($io){
            var_dump('connection success');
            /*$socket->on('factory',function($msg)use($io){
                $msg = json_decode($msg,true);
                var_dump($msg['subFactory']);
                $result = $this->getTableData($msg['subFactory']);
                Timer::add(30,function()use($io,$result){
                    $io->emit('leftMsg', $result['un_car']);
                });
                Timer::add(600,function()use($io,$result){
                    $io->emit('rightMsg', $result['un_sign']);
                });
            });*/
            
        });

        $io->on('disconnect', function($socket){
            var_dump('disconnect');
        });

        Worker::runAll();
    }

    public function getTableData( $subFactory = NULL )
    {  
        
        $query_un_car = 'exec dbo.WGetUnCarPackSum @strWhere = N\'' . ' and p.FactoryId>=\'\' ' . $this->factory_id . '\'\'';
        $query_un_sign = 'exec dbo.WGetUnSignDetail @strWhere = N\'' . ' and d.FactoryId>= \'\'' . $this->factory_id . '\'\'';
        if( $subFactory ){
            $query_un_car .= ' and p.SubFacId<=\'\'' . $subFactory . '\'\'\'';
            $query_un_sign .= ' and d.SubFacId<= \'\'' . $subFactory . '\'\'\'';
        }else{
            $query_un_car .= '\'';
            $query_un_sign .= '\'';
        }
        try {
            $un_car_data = Db::query($query_un_car);
            $un_sign_data = Db::query($query_un_sign);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        /*foreach ($un_car_data as $key => $value) {
            $un_car[] = [
                $un_car[$key]['PCarType'],
                $un_car[$key]['iCount'],
                $un_car[$key]['To5Area']
            ];
        }
        foreach ($un_sign_data as $idx => $val) {
            $un_sign[] = [
                $un_sign[$idx]['CarNo'],
                $un_sign[$idx]['strNo'],
                $un_sign[$idx]['CarPName'],
                $un_sign[$idx]['UnSignCount']
            ];
        }*/
        if( $subFactory == 'DX' ){
            $un_car = [
                    ['1000大车','3','2100'],
                    ['7000大车','4','2100'],
                    ['9000大车','6','2100'],
                    ['2000大车','6','2100'],
                    ['5000大车','3','2100'],
                    ['6000大车','1','2100']
                ];
            for ($i=0; $i <= 1000 ; $i++) {
                if( $i % 2 == 0 ){
                    $un_sign[$i] = ["客户自提","20040468.1","客户自提",'DX'];
                }else{
                    $un_sign[$i] = ["苏NBR500","20090436.1","杨宗领",'DX'];
                }
                
            }
        }else{
            $un_car = [
                    ['A大车','3','2100'],
                    ['C大车','4','2100'],
                    ['D大车','6','2100'],
                    ['V大车','6','2100'],
                    ['B大车','3','2100'],
                    ['A大车','1','2100']
                ];
            for ($i=0; $i <= 1000 ; $i++) {
                if( $i % 2 == 0 ){
                    $un_sign[$i] = ["客户自提","20040468.1","客户自提",'LH'];
                }else{
                    $un_sign[$i] = ["苏NBR500","20090436.1","杨宗领",'LH'];
                }
                
            }
        }
        
        
        return ['un_car'=>$un_car,'un_sign'=>$un_sign];
    }

/*
    public function dx()
    {
        $io = new SocketIO(7777);
        $io->on('workerStart', function($socket)use($io){
            Timer::add(5,function()use($io){
                $time = 'DX' . date('h:i:s',time());
                for ($i=0; $i <= 1000 ; $i++) {
                    if( $i % 2 == 0 ){
                        $data[$i] = ["客户自提","20040468.1","客户自提",$i];
                    }else{
                        $data[$i] = ["苏NBR500","20090436.1","杨宗领",$i];
                    }
                    
                }
                $io->emit('dx',$data);
            });
        });
        $io->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }*/
}
