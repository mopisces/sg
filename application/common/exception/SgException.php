<?php
namespace app\common\exception;

use app\common\exception\BaseException;

class SgException extends BaseException
{
	public $msg = '生管监控服务异常';
	public $errorCode = '10000';

	public function __construct($params = [])
	{
		if (!is_array($params)) {
            return;
        }
        if (array_key_exists('msg', $params)) {
            $this->msg = $params['msg'];
        }
	}
}