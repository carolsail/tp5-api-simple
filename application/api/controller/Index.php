<?php
namespace app\api\controller;

use app\api\BaseController;

use app\api\library\Token;

class Index extends BaseController
{
	
    protected $noNeedLogin = ['index', 'hello', 'login', 'register'];

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
     * hello/:name 或 api/index/hello?name=test
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

    /**
     * 登陆例子
     * api/index/login
     */
    public function login(){
        $account = input('param.account');
        $password = input('param.password');
        if (!$account || !$password)
        {
            $this->error('Invalid parameters');
        }
        $ret = $this->auth->login($account, $password);
        if ($ret)
        {
            $data = ['userinfo' => $this->auth->getUserinfo(), 'token' => $this->auth->getToken()];
            $this->success('Logged in successful', $data);
        }
        else
        {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 会员注册
     * api/index/register
     * @param string $username
     * @param string $password
     * @param string $email 可选
     * @param string $mobile 可选
     * @param array $extend 可选
     */
    public function register(){
        $username = input('param.username');
        $password = input('param.password');
        $ret = $this->auth->register($username, $password);
        if($ret){
            $data = ['userinfo' => $this->auth->getUserinfo(), 'token' => $this->auth->getToken()];
            $this->success('Register in successful', $data);
        }else{
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注销登录
     * api/index/logout
     * 携带token
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success('Logout successful');
    }

    /**
     * 修改密码
     * @param string    $newpassword        新密码
     * @param string    $oldpassword        旧密码
     * @param bool      $ignoreoldpassword  忽略旧密码
     * @return boolean
     */
    public function change(){
        $newpassword = '123456';
        $ret = $this->auth->changePassword($newpassword, '', true);
        if($ret){
            $this->success('Change in successful');
        }else{
            $this->error($this->auth->getError());
        }
    }

    /**
     * 获取用户信息
     */
    public function info(){
        $info = $this->auth->getUserinfo();
        $this->success('successful', $info);
    }
}
