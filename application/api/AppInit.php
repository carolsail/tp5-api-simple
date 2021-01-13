<?php
namespace app\api;

use think\Request;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Env;
use think\facade\Lang;

class AppInit
{
	public function moduleInit(Request $request)
    {
        // 设置mbstring字符编码
        mb_internal_encoding("UTF-8");

        $moduleName = $request->module();
        $appName = Config::get('app.app_name') ?: 'tp5_api';
        if($request->get('lang')){
            Cookie::set('lang_' . $appName, $request->get('lang'));
        }else{
            Cookie::set('lang_' . $appName, Config::get('app.default_lang'));
        }
		// 加载模块公共语言包(zh-cn, zh-hk, en-us...)
        Lang::load(Env::get('app_path') . $moduleName . DIRECTORY_SEPARATOR. 'lang' . DIRECTORY_SEPARATOR . Cookie::get('lang_' . $appName) . '.php');
    }
}