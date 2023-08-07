<?php
namespace app\common\exception;

use think\exception\Handle;
use think\Log;
use Exception;
/**
 * 重写Handle的render方法，实现自定义异常消息
 * Class ExceptionHandler
 * @package app\api\library\exception
 */
class ExceptionHandler extends Handle
{
    private $responseCode = 200;//response http code 
    private $msg;//response http data msg
    private $errorCode;//response http data errorCode
    /**
     * 输出异常信息
     * @param Exception $e
     * @return \think\Response|\think\response\Json
     * 2表示服务错误(1表示系统级错误),02表示应用ID,01表示错误类型(01 参数错误/02 http请求错误/03 内部错误/ 04 无访问权限/ 05 token令牌错误)
     */
    public function render(Exception $e)
    {   
        //是否异常实例
        if ($e instanceof BaseException) 
        {  
            $this->errorCode = $e->errorCode;
            $this->msg = $e->msg;
        }
        // Http异常
        /*elseif ($e instanceof \think\exception\HttpException)
        {
            $this->errorCode = 20202;
            //$this->msg = 'http_error_code:' . $e->getMessage();
            $this->msg = '请求地址错误~';
        }*/
        // 参数验证异常
        elseif($e instanceof \think\exception\ValidateException)
        {
            $this->errorCode = 20201;
            $this->msg = $e->getError() ?: '参数错误~';
        } 
        else {
           	if (config('app_debug')) {   //是否开启debug模式，异常交给父类异常处理，否则输出json格式错误
                return parent::render($e);
            }
            $this->responseCode = 500;
            $this->errorCode = 10214;
            $this->msg = '内部错误,请稍后再试~';
        }
        return json(['msg' => $this->msg, 'errorCode' => $this->errorCode ,'result'=>null],$this->responseCode);
    }
}
