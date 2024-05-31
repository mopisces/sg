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
			'hostport' => isset($data['DB_PORT']) ? $data['DB_PORT'] : '1434',
			'dsn'      => 'sqlsrv:Server=' . $data['DB_HOST'] . ';Database=' . $data['DB_NAME']
		];
	}

	public function byteTostr( $hex )
	{
		$str = '';
        for( $i = 0 ; $i < strlen( $hex ) - 1; $i += 2 ){
            $str .= chr( hexdec( $hex[$i].$hex[ $i + 1 ] ) );
        }
        return $str;
	}

	public function sqlsrvConn( $conn )
	{
		$conn = sqlsrv_connect($conn['hostname'],[
			'Database'     => $conn['database'],
			'UID'          => $conn['username'],
			'PWD'          => $conn['password'],
			'CharacterSet' => 'UTF-8'
		]);

		if( $conn === false ){
			throw new \app\common\exception\SgException(['msg'=>'数据库链接不可用,请检查是否开机']);
		}

		return $conn;
	}

	public function getLanguage($language="zh") 
	{
		$langFile = __DIR__ . '/../lang/' . $language . '.php';
		if (file_exists($langFile)) {
			return include $langFile;
		} else {
			return include __DIR__ . '/../lang/zh.php';
		}
	}
}