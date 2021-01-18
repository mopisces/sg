<?php
namespace app\sg\controller\v1;

use think\Controller;
use think\Db;
use util;

class SimulatedAnnealing extends Controller
{
	private $map;       //地图，按照矩阵的方式
	private $n;         //地图规模
	private $T;         //初始温度T
    private $L;         //每个温度下达到降温状态的迭代次数
    private $l;         //降温常数小于却接近于1的常数。λ通常的取值在0.8至0.99之间。在每一温度下，实验足够多的转移次数
    private $ord_temp;  //降温终止条件，相当于降温到常温
    private $path;      //输出结果

    public function __construct()
    {
        $map = [
            [120.212443,30.251446],
            [120.218236,30.250816],
            [120.215447,30.251706],
            [120.212915,30.248629],
            [120.216412,30.24709]
        ];
    	$this->getMap();
        $this->T = 20;
        $this->L = 100;
        $this->l = 0.95;
        $this->ord_temp = 1;
    	parent::__construct();
    }

    public function getMap()
    {
        /*$this->map = [
            [0,3,6,7],
            [5,0,2,3],
            [6,4,0,2],
            [3,7,5,0]
        ];*/
        /*$newMap = [];
        foreach ($map as $key => $value) {
            $newMap[] = [];
        }*/
        $this->map = $this->getDistanceNew();
        $this->n = count($this->map);
    }

    public function getDistance( )
    {
        /*$start = [120.216412,30.24709];*/
        $target = [
            [120.215532,30.249741],
            [120.215447,30.251706],
            [120.212915,30.248629],
            [120.218236,30.250816],
            [120.212443,30.251446],
            [120.216412,30.24709]
        ];
        $total = count($target);
        for ($i = 0; $i <= $total - 1; $i++) {
            $destination = array_pop($target);
            //dump($target);
            $origins = '';
            foreach ($target as $key => $value) {
                if($key == count($target) - 1){
                    $origins .= $value[0] . ',' . $value[1];
                }else{
                    $origins .= $value[0] . ',' . $value[1] . '|';
                }
            }
            //dump($origins);
            $url = 'https://restapi.amap.com/v3/distance?origins=' . $origins . '&destination=' . $destination[0] . ',' . $destination[1] .'&output=json&key=a4346f65aebf83860d886772220b4915';
            $res = json_decode($this->curlGet($url),true)['results'];
            //dump($res);
        }
    }

    public function getDistanceNew()
    {
        $target = [
            [120.215532,30.249741],
            [120.212443,30.251446],
            [120.218236,30.250816],
            [120.215447,30.251706],
            [120.212915,30.248629],
            [120.216412,30.24709]
        ];
        $map = [];
        for ($i=0; $i <= count($target) - 1; $i++) { 
            $origins = '';
            foreach ($target as $key => $value) {
                if( $key == $i ){
                    $destination = $value;
                }else{
                    $origins .= $value[0] . ',' . $value[1] . '|';
                }
                //$res = json_decode($this->curlGet($url),true);
                //dump($res);
            }
            $url = 'https://restapi.amap.com/v3/distance?origins=' . substr($origins,0,strlen($origins)-1) . '&destination=' . $destination[0] . ',' . $destination[1] .'&output=json&key=a4346f65aebf83860d886772220b4915';
            $res = json_decode($this->curlGet($url),true);
            $distance = [];
            foreach ($res['results'] as $key => $value) {
                $distance[] = $value['distance'];
            }
            array_splice($distance, $i, 0, [0]);
            $map[] = $distance;
        }
        return $map;
    }

    public function curlGet($url)
    {
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        if (curl_error($ch)) {
            throw new \app\common\exception\SgException(['msg'=>'服务不可用']);
        }else{
            curl_close($ch);
            return $response;
        }
    }

    public function randomFloat($min = 0, $max = 1) 
    {
    	return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    public function output()
    {
    	foreach($this->path as $key => $value)
    	{
    		echo $value."->";
    	}
    }

    public function new_S($_S)
    {
        //dump($_S);
    	$temp_S = $_S;
    	$shift = rand(1,$this->n-2);
    
    	$ts = $temp_S[1]; 
    	$temp_S[1] = $temp_S[1+$shift];
        $temp_S[1+$shift] = $ts;
        return $temp_S;
    }

    public function cost($_S)
    {
        //$_S = [0,3,2,1,4,5,0];
    	$_cost = 0;
    	for($i=0;$i<$this->n-1;$i++)
    	{
            try {
               $_cost += $this->map[$_S[$i]][$_S[$i+1]]; 
            } catch ( \Exception $e) {
                die;
            }
    		//$_S = [0,3,2,1,0] 7 + 5 + 4 = 16
    	}
    	$_cost += $this->map[$_S[$i]][0];
    	return $_cost;
    }

    public function calculate()
    {
    	$S = array();

    	for($i=0;$i<$this->n;$i++)
    	{
    		$S[$i] = $i;
    	}
    	$S[] = 0;
        $this->path = $S;
    	$t = $this->T;
    	while($t > $this->ord_temp)
    	{
    		for($i=0;$i<$this->L;$i++)
    		{
    			$S1 = $this->new_S($S);
    			$K = $this->cost($S1) - $this->cost($S);
    			if($K < 0){
    				if($this->cost($S) < $this->cost($this->path)){
    					$this->path = $S;
    				}
    			}else{
    				if($this->randomFloat(0,1) < exp(-$K/$t)){
    					$S = $S1;
    				}
    			}
    		}
    		$t = $t * $this->l;
    	}
    	$this->output();
    	echo '<br>The min cost is '.$this->cost($this->path);
    }
}

