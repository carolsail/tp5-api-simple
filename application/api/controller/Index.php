<?php
namespace app\api\controller;

use app\api\BaseController;

class Index extends BaseController
{
	
    /**
     * 响应多语言
     * api/index/index
     */
    public function index()
    {
        $lang = __('Please login first');
        $this->success('操作成功', ['name'=>'test', 'age'=>10, 'lang'=>$lang]);
    }

    /**
     * 路由实例
     * hello/:name
     */
    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    /**
     * 验证器实例
     * api/index/validateDemo?type=array
     */
    public function validateDemo($type='array'){
    	$data = [
    		'name' => 'test',
    		'age'  => 150,
    		'email' => '123.com'
    	];
        if($type == 'array'){
            $rule = [
                'name'  => 'require|max:25',
                'age'   => 'number|between:1,120',
                'email' => 'email'
            ];
            $msg = [
                'name.require' => '姓名必填',
                'name.max' => '姓名最长25',
                'age.number' => '年龄格式不对',
                'age.between' => '年龄1~120间',
                'email.email' => '邮箱格式有误'
            ];
            $this->validateFailException();
            $this->validate($data, $rule, $msg, true);
        }else{
            $this->validateFailException();
            $this->validate($data, 'app\api\validate\Test'); 
        }
    }

}
