<?php
namespace app\api\behavior;

class CORS{
	public function appInit($params) {
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, token");
		header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS,PATCH');
        //跨域复杂请求的时候options请求拦截
        if(request()->isOptions()){
        	exit();
        }
	}
}