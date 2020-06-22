<?php
namespace app\util;

use think\Db;

class Util 
{
	public function byteToInt( $arr, $k )
	{
		$r = $arr[$k + 3] & 0xff;
	    $r <<= 8;
	    $r |= $arr[$k + 2] & 0xff;
	    $r <<= 8;
	    $r |= $arr[$k + 1] & 0xff;
	    $r <<= 8;
	    $r |= $arr[$k] & 0xff;
	    return $r;
	}
	
	public function getConnect( $data )
	{
		return [
			'type'     => $data['DB_TYPE'],
			'hostname' => $data['DB_HOST'],
			'database' => $data['DB_NAME'],
			'username' => $data['DB_USER'],
			'password' => $data['DB_PWD'],
			'hostport' => '',
			'dsn'      => ''
		];
	}
}