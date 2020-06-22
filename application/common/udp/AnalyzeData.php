<?php
namespace app\common\udp;

use think\facade\Config;
use util;

class AnalyzeData
{
	public static function analyzeUdp( $config_index )
	{
		$config = Config::get('app.db_config')[$config_index];
		try {
			$socket = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
			socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,['sec' => 1,'usec' => 0]);
			socket_bind($socket, $config['socket_bind']['address'], $config['socket_bind']['port']);
			socket_recvfrom($socket,$buf,2048,0,$from,$port);
			socket_close($socket);
			$buf = unpack('C*',$buf);
			$result = self::analyzeData( $buf, $config );
		} catch ( \Exception $e ) {
			return socket_last_error();
		}
		return $result;
	}

	protected static function analyzeData( $buf, $config )
	{
		$pre_flag = '';
		for ( $i = 1;  $i <= strlen($config['socket_bind']['flag']);  $i++ ){ 
			$pre_flag .= chr( $buf[$i] );
		}
		if( $pre_flag === $config['socket_bind']['flag'] ){
			if( $config['isnew'] ){
				if( $config['updown'] ){
					$result = self::getNewUpdown( $buf );

				}else{
					$result = self::getNewNotUpdown( $buf );
				}
			}else{
				if( $config['updown'] ){
					$result = self::getNotNewUpdown( $buf, $config );
				}else{
					$result = self::getNotNewNotUpdown( $buf, $config );
				}
			}
		}
		return $result;
	}

	protected static function getNewUpdown( $buf )
	{
		$result = [
			'class'  => chr($buf[709]),
			'qds1'   => util::byteToInt($buf,53),
			'scds1'  => util::byteToInt($buf,17),
			'syds1'  => util::byteToInt($buf,21),
			'ddsy1'  => round(util::byteToInt($buf,33)/1000,0),
			'blds1'  => util::byteToInt($buf,25),
			'ddc'    => round(util::byteToInt($buf,61)/1000,0),
			'qc'     => util::byteToInt($buf,57),
			'qds2'   => util::byteToInt($buf,105),
			'scds2'  => util::byteToInt($buf,69),
			'syds2'  => util::byteToInt($buf,73),
			'blds2'  => util::byteToInt($buf,77),
			'cs'     => util::byteToInt($buf,133),
			'benban' => [
				'zms'   => util::byteToInt($buf,149),
				'sc'    => util::byteToInt($buf,153),
				'sy'    => util::byteToInt($buf,157),
				'bl'    => util::byteToInt($buf,161),
				'js'    => util::byteToInt($buf,189)/10,
				'scpf'  => util::byteToInt($buf,165),
				'scsj'  => util::byteToInt($buf,193),
				'tcsj'  => util::byteToInt($buf,197),
				'tccs'  => util::byteToInt($buf,201),
				'scjpf' => util::byteToInt($buf,169),
				'hzl'   => util::byteToInt($buf,181)/100,
				'xbl'   => util::byteToInt($buf,185)/100,
			],
			'benbi' => [
				'zms'   => util::byteToInt($buf,205),
				'sc'    => util::byteToInt($buf,209),
				'sy'    => util::byteToInt($buf,213),
				'bl'    => util::byteToInt($buf,217),
				'js'    => util::byteToInt($buf,245)/10,
				'scpf'  => util::byteToInt($buf,221),
				'scsj'  => util::byteToInt($buf,249),
				'tcsj'  => util::byteToInt($buf,253),
				'tccs'  => util::byteToInt($buf,257),
				'scjpf' => util::byteToInt($buf,225),
				'hzl'   => util::byteToInt($buf,237)/100,
				'xbl'   => util::byteToInt($buf,241)/100,
			],
			'huiji' => [
				'cs' => util::byteToInt($buf,265),
				'sy' => round(util::byteToInt($buf,285)/1000,0),
				'lj' => round(util::byteToInt($buf,289)/1000,0),
			],
			'SF1' => [
				'cs' => util::byteToInt($buf,393),
				'sy' => round(util::byteToInt($buf,413)/1000,0),
				'lj' => round(util::byteToInt($buf,417)/1000,0),
			],
			'SF2' => [
				'cs' => util::byteToInt($buf,521),
				'sy' => round(util::byteToInt($buf,541)/1000,0),
				'lj' => round(util::byteToInt($buf,545)/1000,0),
			],
			'SF3' => [
				'cs' => util::byteToInt($buf,649),
				'sy' => round(util::byteToInt($buf,669)/1000,0),
				'lj' => round(util::byteToInt($buf,673)/1000,0),
			]
		];
		return $result;
	}

	protected static function getNewNotUpdown( $buf )
	{
		$result = [
			'class'  => chr($buf[709]),
			'qds'    => util::byteToInt($buf,53),
			'scds'   => util::byteToInt($buf,17),
			'syds'   => util::byteToInt($buf,21),
			'ddsy'   => round(util::byteToInt($buf,33)/1000,0),
			'blds'   => util::byteToInt($buf,25),
			'ddc'    => round(util::byteToInt($buf,61)/1000,0),
			'qc'     => util::byteToInt($buf,57),
			'cs'     => util::byteToInt($buf,133),
			'benban' => [
				'zms'   => util::byteToInt($buf,149),
				'sc'    => util::byteToInt($buf,153),
				'sy'    => util::byteToInt($buf,157),
				'bl'    => util::byteToInt($buf,161),
				'js'    => util::byteToInt($buf,189)/10,
				'scpf'  => util::byteToInt($buf,165),
				'scsj'  => util::byteToInt($buf,193),
				'tcsj'  => util::byteToInt($buf,197),
				'tccs'  => util::byteToInt($buf,201),
				'scjpf' => util::byteToInt($buf,169),
				'hzl'   => util::byteToInt($buf,181)/100,
				'xbl'   => util::byteToInt($buf,185)/100,
			],
			'benbi'  => [
				'zms'   => util::byteToInt($buf,205),
				'sc'    => util::byteToInt($buf,209),
				'sy'    => util::byteToInt($buf,213),
				'bl'    => util::byteToInt($buf,217),
				'js'    => util::byteToInt($buf,245)/10,
				'scpf'  => util::byteToInt($buf,221),
				'scsj'  => util::byteToInt($buf,249),
				'tcsj'  => util::byteToInt($buf,253),
				'tccs'  => util::byteToInt($buf,257),
				'scjpf' => util::byteToInt($buf,225),
				'hzl'   => util::byteToInt($buf,237)/100,
				'xbl'   => util::byteToInt($buf,241)/100,
			],
			'huji' => [
				'cs' => util::byteToInt($buf,265),
				'sy' => round(util::byteToInt($buf,285)/1000,0) ,
				'lj' => round(util::byteToInt($buf,289)/1000,0),
			],
			'SF1' => [
				'cs' => util::byteToInt($buf,393),
				'sy' => round(util::byteToInt($buf,413)/1000,0),
				'lj' => round(util::byteToInt($buf,417)/1000,0),
			],
			'SF2' => [
				'cs' => util::byteToInt($buf,521),
				'sy' => round(util::byteToInt($buf,541)/1000,0),
				'lj' => round(util::byteToInt($buf,545)/1000,0),
			],
			'SF3' => [
				'cs' => util::byteToInt($buf,649),
				'sy' => round(util::byteToInt($buf,669)/1000,0),
				'lj' => round(util::byteToInt($buf,673)/1000,0),
			]
		];
		return $result;
	}

	protected static function getNotNewUpdown( $buf, $config )
	{
		$result = [
			'class'  => chr($buf[strlen($config['socket_bind']['flag']) + 1]),
			'qds1'   => util::byteToInt($buf,31),
			'scds1'  => util::byteToInt($buf,35),
			'syds1'  => util::byteToInt($buf,39),
			'ddsy1'  => util::byteToInt($buf,43),
			'blds1'  => util::byteToInt($buf,47),
			'ddc1'   => util::byteToInt($buf,51),
			'qc1'    => util::byteToInt($buf,55),
			'qds2'   => util::byteToInt($buf,59),
			'scds2'  => util::byteToInt($buf,63),
			'syds2'  => util::byteToInt($buf,67),
			'ddsy2'  => util::byteToInt($buf,71),
			'blds2'  => util::byteToInt($buf,75),
			'ddc2'   => util::byteToInt($buf,79),
			'qc2'    => util::byteToInt($buf,83),
			'xs'     => util::byteToInt($buf,87),
			'benban' => [
				'zms'   => util::byteToInt($buf,91),
				'sc'    => util::byteToInt($buf,95),
				'sy'    => util::byteToInt($buf,99),
				'bl'    => util::byteToInt($buf,103),
				'js'    => util::byteToInt($buf,107)/10,
				'scpf'  => util::byteToInt($buf,111),
				'scsj'  => util::byteToInt($buf,115),
				'tcsj'  => util::byteToInt($buf,119),
				'tccs'  => util::byteToInt($buf,123),
				'scjpf' => util::byteToInt($buf,127),
				'hzl'   => round(util::byteToInt($buf,131)/10/util::byteToInt($buf,111)*1000,1),
				'xbl'   => util::byteToInt($buf,135)/10,
			],
			'benbi'  => [
				'zms'   => util::byteToInt($buf,139),
				'sc'    => util::byteToInt($buf,143),
				'sy'    => util::byteToInt($buf,147),
				'bl'    => util::byteToInt($buf,151),
				'js'    => util::byteToInt($buf,155)/10,
				'scpf'  => util::byteToInt($buf,159),
				'scsj'  => util::byteToInt($buf,163),
				'tcsj'  => util::byteToInt($buf,167),
				'tccs'  => util::byteToInt($buf,171),
				'scjpf' => util::byteToInt($buf,175),
				'hzl'   => round(util::byteToInt($buf,179)/10/util::byteToInt($buf,159)*1000,1),
				'xbl'   => util::byteToInt($buf,183)/10,
			],
			'huji' => [
				'cs' => util::byteToInt($buf,187)/10,
			],
			'SF1' => [
				'cs' => util::byteToInt($buf,307)/10,
			],
			'SF2' => [
				'cs' => util::byteToInt($buf,427)/10,
			],
			'SF3' => [
				'cs' => util::byteToInt($buf,547)/10,
			]
		];
		return $result;
	}

	protected static function getNotNewNotUpdown( $buf, $config )
	{
		$result = [
			'class'  => chr($buf[strlen($config['socket_bind']['flag']) + 1]),
			'qds'    => util::byteToInt($buf,31),
			'scds'   => util::byteToInt($buf,35),
			'syds'   => util::byteToInt($buf,39),
			'ddsy'   => round(util::byteToInt($buf,43)/1000,1),
			'blds'   => util::byteToInt($buf,47),
			'ddc'    => util::byteToInt($buf,51),
			'qc'     => util::byteToInt($buf,55),
			'cs'     => round(util::byteToInt($buf,59)/10,1),
			'benban' => [
				'zms'   => util::byteToInt($buf,63),
				'sc'    => util::byteToInt($buf,67),
				'sy'    => util::byteToInt($buf,71),
				'bl'    => util::byteToInt($buf,75),
				'js'    => util::byteToInt($buf,79)/10,
				'scpf'  => util::byteToInt($buf,83),
				'scsj'  => util::byteToInt($buf,87),
				'tcsj'  => util::byteToInt($buf,91),
				'tccs'  => util::byteToInt($buf,95),
				'scjpf' => util::byteToInt($buf,99),
				'hzl'   => round(util::byteToInt($buf,103)/10/util::byteToInt($buf,83)*1000,1),
				'xbl'   => util::byteToInt($buf,107)/10,
			],
			'benbi'   => [
				'zms'   => util::byteToInt($buf,111),
				'sc'    => util::byteToInt($buf,115),
				'sy'    => util::byteToInt($buf,119),
				'bl'    => util::byteToInt($buf,123),
				'js'    => util::byteToInt($buf,127)/10,
				'scpf'  => util::byteToInt($buf,131),
				'scsj'  => util::byteToInt($buf,135),
				'tcsj'  => util::byteToInt($buf,139),
				'tccs'  => util::byteToInt($buf,143),
				'scjpf' => util::byteToInt($buf,147),
				'hzl'   => round(util::byteToInt($buf,151)/10/util::byteToInt($buf,131)*1000,1),
				'xbl'   => util::byteToInt($buf,155)/10,
			],
			'huji' => [
				'cs' => util::byteToInt($buf,159)/10,
			],
			'SF1' => [
				'cs' => util::byteToInt($buf,279)/10,
			],
			'SF2' => [
				'cs' => util::byteToInt($buf,399)/10,
			],
			'SF3' => [
				'cs' => util::byteToInt($buf,519)/10,
			]
		];
		return $result;
	}

}