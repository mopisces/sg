<?php
namespace app\common\exception;

use app\common\exception\BaseException;

class AuthException extends BaseException
{
	public $msg = '登陆状态异常,请重新登陆';
	public $errorCode = '11000';
}