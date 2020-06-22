<?php

namespace app\common\exception;

use think\Exception;

/**
 * Class BaseException
 * 自定义异常类的基类
 */
class BaseException extends Exception
{
    public $msg = '内部错误,请稍后再试~';
    public $errorCode = 10203;
    /**
     * 构造函数，接收一个关联数组
     * @param array $params 关联数组只应包含code、msg，且不应该是空值
     */
    public function __construct($params = [])
    {
        if (!is_array($params)) {
            return;
        }
        if (array_key_exists('msg', $params)) {
            $this->msg = $params['msg'];
        }
        if (array_key_exists('errorCode', $params)) {
            $this->errorCode = $params['errorCode'];
        }
    }
}

