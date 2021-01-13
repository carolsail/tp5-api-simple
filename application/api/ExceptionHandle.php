<?php
namespace app\api;

use Exception;
use think\exception\Handle;

class ExceptionHandle extends Handle 
{
	public function render(Exception $e)
    {
        if (!\think\facade\Config::get('app.app_debug'))
        {
            $statuscode = $code = 500;
            $msg = 'An error occurred';
            // 验证异常
            if ($e instanceof \think\exception\ValidateException)
            {
                $code = 0;
                $statuscode = 200;
                $msg = $e->getError();
            }
            // Http异常
            if ($e instanceof \think\exception\HttpException)
            {
                $statuscode = $code = $e->getStatusCode();
            }
            return json(['code' => $code, 'msg' => $msg, 'time' => time(), 'data' => null], $statuscode);
        }
    	return parent::render($e);
    }
}