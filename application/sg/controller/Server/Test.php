<?php
namespace app\sg\controller\Server;

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Lib\Timer;
use think\Controller;
use think\Db;
use app\common\udp\AnalyzeData;
use util;

class Test extends Controller
{
    protected $socket;
    protected $config_index = NULL;

    public function setKeyPem($keyStr, $type = 'public')
    {
        if ($type == 'private') {
            $begin_key = "-----BEGIN PRIVATE KEY-----\n";
            $end_key = "-----END PRIVATE KEY-----\n";
            $filename = 'ysb_pri.pem';
        }else{
            $begin_key = "-----BEGIN PUBLIC KEY-----\n";
            $end_key = "-----END PUBLIC KEY-----\n";
            $filename = 'ysb_pub.pem';
        }
        $fp = fopen($filename, 'ab');
        $len = fwrite($fp, $begin_key, strlen($begin_key));

        $raw = strlen($keyStr) / 64;
        $index = 0;
        $keyData = '';
        while ($index <= $raw) {
            $line = substr($keyStr, $index * 64, 64) . "\n";
            if (strlen(trim($line)) > 0){
                $len += fwrite($fp, $line, strlen($line));
            }
            $index++;
        }
        $len += fwrite($fp, $end_key, strlen($end_key));
        fclose($fp);
        return $len;
    }

    public function check()
    {
        $arr = [
            'tran_code' => 'jsPay',
            'mchnt_cd' => '333021170110101',
            'sub_appid' => 'wx8f9065d8c32ab018',
            'sub_openid'=>'123456789',
            'notify_url' => 'http://lpkj.leaper.ltd:50001/public/v1/ysb/notify',
            'trace_no' => 'WS23050850551004',
            'isCredit' => false
        ];
        $str = json_encode($arr);
        var_dump($str);
    }

    public function index()
    {
        $this->check();
        die();
        $private_str = 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQCYSrlBq0YEoelewlv/ySItjAaegGFQbNtzoaxZKZR0RXTaO9YoM0cUnUhtvGMBERcG22hZ+wCztKUF2tACM05H49V4CWXVtIIYRZR0FrPoLSfscRUGvEzyODJ8lfJd2Ph+Mp9UhxMhBGUtOM1wtsqD9lvr+mUix29zRLqTjvH79i6Kws/r7wOQr4BewTSaips03C2KyaDbDlOEwAAPpwyCiHt/KyI9l/VQM/13F0GxMEvCK/LL+iERtWgmX5fRp+1qREf90HJVD41qGfFm+TtOv8Hp+FUk+je1VVy3sFszmEi+FS6Tb5FadPSl17cGB6KDAgizRs39LPkXffQ7K30PAgMBAAECggEAOiivPwjtoG9E9E89Wx2w07wZ+wYEI/auiCZB73sVmqG70mvviUKr7o9yTZYYKRwhsxivbU2SIw7lxqqdrXlyd7nmmATewxJAbIyF+R+CbTRxfrZg1UWsDQSxIysQeA6YN3mVp24+O+m1yeNbcaQdCvBWgnQJk5KeWWLx7dA1UYdHkTvyI9zK/cs7vfBzB9mONGXGvw93Kvn+JJtNETLu+agHclDqP+Zz5gNQzDd3okbBG5Xl+XWPV57JmctKJzwfOUK3fEbWYHREp9r8Eb/zU/7jltiDcv8rMOEZMaqe7Ajq/g3X+4PC24YKgQ7CnpK5Kw8y2vwLkEmVfZtIWlWMAQKBgQDQKu95/AyUpgxZW96Wg+2Y6PrFLxiHaxQRM3ak4CbAGvaVVB90rXeNON8LZYSHT4831eVyTrgtzy1tRB5FGXHH/8sptF+33Js1TeHGkKCRG9009DeKo0C27xk1XdPaO2ms28GZckbcW06eXcAchrqz8AjuXu9HiN63ZAg0jqEfjwKBgQC7SQAaKdRzzL9JHyerta5/zRZw8Qv85S9QM6ZgvqjsqMwb0tS5de8wXHwc1CiEMXLkW1OekVnq8TaB5Qc5vHF8y2EDIVIY8te2nucodO3zG77rKuZJLpnlGG24cgCqeF4pQMntKbZzS7td3CXN1LcwThHgk8UmLgtYAezpY9EKgQKBgQCxLEnkdeyzO9x41aNOUf0QTwLYfuwOnclXloidbZYtnQVQNWgMY3PEQ6o6xe2VSNapfCisMGm7u4B7ZiWpRC2+PmzJExcAoweKx72oxgGTX7bXUiBPpAD5cejvresY68ZWdDeDhWrgM+pnCX3wCY+whFTlpPFc3hZI2h/Ns7NY6wKBgQCfMacXljTTldeG9SeIam+AjLilmg+BkOJNvwmOtKHsQHL2t5hAaQG+zu5kpuYlr86kUwjaAV04On8FnMMujYK9/DZjLcCWGl3ICnwcxH+6pPv888M5s5X2yXQCU01DmfsX/81kfY9ro5UJbGbp9gUc10+U4Ka1FHBqB4D0xfbbgQKBgQCpR54IbzIiTjYQ5DugwLEeCqr/ioswh8pXuWrmYjKcwYFG/XMaCnd7TFzTVEr0/Fx4Vi5GV5rfVaCe5z7DPY5c1hiygG0dPs8cNplCXyzih1o6IrRJq77LWdEWVRtW4jmDaAy1+DY/il/0JmGqQ+w0R4fyoSltLcYDJ7GDW2G7DQ==';
        $public_str = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8cD7CtlL6nzfDPWlzrCEB6AyB5Sq/wO/SMhOmCa2CaedTSAWvZD1gOwZfO7j7VkDxaRFCtcwjWZ+revHdPkXpx+N2ZqOdc0tumFCEsjXnhZEwXCWu0LoFauV4ZhIoay+t/bfhSbI7eKUz3clle7jELJ22l3W8Rua/wqKIZooUPpIg9cmF8zl5nPi2JJdg6ojX1HVVbJ/KfPe5Sk4kaJ0s+Wn7FwmjkwHafCXoQZXUKXg7slV03CdoESZx0RiK1QbGtSWqLk0EMKXaE/pzaiwrQTJuo1jM0FTFNL+27QQmb261kfRA/HbLKkEDFO824i4VMulq0n9kFDyViGXG2GB9wIDAQAB';
        $this->setKeyPem($public_str);
        $this->setKeyPem($private_str, 'private');
    }

   /*public function index()
    {
        $this->socket = new SocketIO(40000);
        $config =[ ['UDP_NAME'=>'XLK'], ['UDP_NAME'=>'SCLX']];
        $this->socket->on('workerStart', function($socket)use($config){
            Timer::add(1,function()use($config){
                foreach ($config as $key => $value) {
                    $this->socket->emit($value['UDP_NAME'],$value['UDP_NAME']);
                }
            });
        });

        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }*/

    /*public function index($config_index)
    {
        $this->config_index = $config_index;
        $config = config('db_config')[ $this->config_index ];
        $this->socket = new SocketIO($config['socketio']['port']);
        $this->socket->on('workerStart', function($socket)use($config){
            Timer::add(1,function()use($config){
                if( $config['DB_DATA'] ){
                    $data = json_encode($this->getDataFromDB());
                }else{
                    $data = json_encode($this->analyzeUDP());
                }
                $this->socket->emit('AnalyUdpData' . $this->config_index,$data );
            });
        });
        $this->socket->on('disconnect', function($socket){
            var_dump('disconnect');
        });
        Worker::runAll();
    }*/

    protected function analyzeUDP()
    {
        return AnalyzeData::analyzeUdp($this->config_index);
    }

    protected function getDataFromDB()
    {
        $config = config('db_config')[ $this->config_index ];
        $connect = util::getConnect($config);
        $data = Db::connect($connect)->table('proddata')->where('id',1)->value('data');
        /*$data = '0xD01B0500010000009F080000220B00003A000000A20300005870280000000000000000002D000000A2030000C1130000A2030000FC4D1F002675280000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000002C010000D200000060000000000000006C07000058702800F09000000E340000E25C0000420000001E6B0000386900007F000000E60100002E000000B100000002040000581A000019040000040000005F120000040800005A0A0000360000003A0F00000D0F0000660000002D00000005010000730000006602000025070000B10000000200000000000000600000000000000048EE0000000000000000000074822700E414CF00540000006C07000022C34700E8030000540000000807000015E50500E803000000000000000000000000000078E6000000000000000000000000000000000000330000006C07000029DF2900E60400002D00000000000000000000000000000000000000000000000000000078E6000000000000000000000000000000000000380000006C07000029DF2900E80300002D00000000000000000000000000000000000000000000000000000098DD020000000000000000001A306D002E6D2001330000006C0700006BA571005C050000330000000807000015E505005C05000000000000640000006500000098DD02000000000000000000249325003039D200570000006C07000022C34700E8030000460000006C07000029DF2900E80300000000000000000000000000000CBF020000000000000000005733FCFF3D06C200330000000807000015E50500BC050000310000000807000040AF0B00BC0500000000000000000000000000000CBF02000000000000000000F440FDFF502B8400460000000807000015E50500E8030000410000000807000040AF0B00E803000041000000323032312D30332D31312030383A3436000000000000000000000000751D00000A0000005A0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000C0000006A000000010000000100000000000000010000000000000000000000030000000000000000000000000000000000000000000000000000000000000000000000000000001E0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000004201000000000000000000000000000000000000000000000000000000000000000000005824000000000000';*/
        $data = substr(str_replace(" ", '', $data),2);
        return AnalyzeData::analyzeUdp( $this->config_index, $this->byteTostr($data) );
    }

    public function byteTostr($hex)
    {
        $str = '';
        for($i=0;$i<strlen($hex)-1;$i+=2){
            $str.=chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $str;
    }
}


