<?php

namespace app\api\validate;

use think\Validate;

class Test extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'name'  => 'require|max:25',
        'age'   => 'number|between:1,120',
        'email' => 'email'
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'name.require' => '姓名必填',
        'name.max' => '姓名最长25',
        'age.number' => '年龄格式不对',
        'age.between' => '年龄1~120间',
        'email.email' => '邮箱格式有误'
    ];
}
