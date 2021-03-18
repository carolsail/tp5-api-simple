<?php

namespace app\api\library;

use think\facade\Hook;
use think\facade\Validate;
use think\Db;
use libs\Random;
use libs\Date;

class Auth{
	protected static $instance = null;
    protected $_error = '';              // 错误信息
    protected $_logined = false;         // 登录状态
    protected $_user = null;             // 用户信息
    protected $_token = '';              // token信息
    protected $config = [
        'auth_user' => 'user'            // 用户信息表
    ];
	protected $allowFields = ['id', 'username', 'nickname', 'email', 'mobile', 'avatar', 'gender'];

	public function __construct(){
    	// 配置options
    	// Token::init();
	}

	/**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = []){
    	if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 根据Token初始化
     *
     * @param string       $token    Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }      

        if ($this->_error) {
            return false;
        }

        $data = Token::get($token);
        if (!$data) {
            return false;
        }

        $auth_id = intval($data['auth_id']);
        if ($auth_id > 0) {
            $user = Db::table($this->config['auth_user'])->where('id', $auth_id)->find();
            if (!$user) {
                $this->setError('Account not exist');
                return false;
            }
            if ($user['status'] != 'normal') {
                $this->setError('Account is locked');
                return false;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_user);
            return true;
        } else {
            $this->setError('You are not logged in');
            return false;
        }
    }	

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 用户登录
     *
     * @param string    $account    账号:用户名、邮箱、手机号
     * @param string    $password   密码
     * @return boolean
     */
    public function login($account, $password)
    {
        $field = Validate::is($account, 'email') ? 'email' : (Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'username');
        $user = Db::table($this->config['auth_user'])->where([$field => $account])->find();

        if (!$user) {
            $this->setError('Account is incorrect');
            return false;
        }

        if ($user['status'] != 'normal') {
            $this->setError('Account is locked');
            return false;
        }

        if ($user['password'] != get_encrypt_password($password, $user['salt'])) {
            $this->setError('Password is incorrect');
            return false;
        }
        
        //判断连续登录和最大连续登录
        if ($user['login_time'] < Date::unixtime('day')) {
            $user['login_successions'] = $user['login_time'] < Date::unixtime('day', -1) ? 1 : $user['login_successions'] + 1;
            $user['login_max_successions'] = max($user['login_successions'], $user['login_max_successions']);
        }
        $user['login_prev_time'] = $user['login_time'];
        $user['login_time'] = time();
        $user['login_ip'] = request()->ip();
        //记录登陆信息
        Db::table($this->config['auth_user'])->update($user);

        //用户信息
        $this->_user = $user;
        //设置token
        $this->_token = Random::uuid();
        Token::set($this->_token, $user['id'], config('token.expire'));
        $this->_logined = true;
        //登录成功的事件
        Hook::listen("user_login_successed", $this->_user);
        return true;
    }
    /**
     * 注册用户
     *
     * @param string $username  用户名
     * @param string $password  密码
     * @param string $email     邮箱
     * @param string $mobile    手机号
     * @param array $extend    扩展参数
     * @return boolean
     */
    public function register($username, $password, $email = '', $mobile = '', $extend = [])
    {
        // 检测用户名或邮箱、手机号是否存在
        if (Db::table($this->config['auth_user'])->where('username', $username)->find()) {
            $this->setError('Username already exist');
            return false;
        }
        if ($email && Db::table($this->config['auth_user'])->where('email', $email)->find()) {
            $this->setError('Email already exist');
            return false;
        }
        if ($mobile && Db::table($this->config['auth_user'])->where('mobile', $mobile)->find()) {
            $this->setError('Mobile already exist');
            return false;
        }
        $ip = request()->ip();
        $time = time();

        $data = [
            'username' => $username,
            'password' => $password,
            'email'    => $email,
            'mobile'   => $mobile,
            'avatar'   => '',
        ];
        $params = array_merge($data, [
            'nickname'  => $username,
            'salt'      => Random::alnum(),
            'login_prev_time'  => $time,
            'login_time' => $time,
            'login_ip'   => $ip,
            'status'    => 'normal',
            'create_time'  => $time
        ]);
        $params['password'] = get_encrypt_password($password, $params['salt']);
        $params = array_merge($params, $extend);

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $userId = Db::table($this->config['auth_user'])->insertGetId($params);
            Db::commit();

            //用户信息
            $this->_user = Db::table($this->config['auth_user'])->where('id', $userId)->find();
            //设置Token
            $this->_token = Random::uuid();
            Token::set($this->_token, $userId, config('token.expire'));

            //注册成功的事件
            Hook::listen("user_register_successed", $this->_user);
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $data = $this->_user;
        $allowFields = $this->getAllowFields();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        $userinfo = array_merge($userinfo, Token::get($this->_token));
        return $userinfo;
    }

    /**
     * 注销
     *
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        //删除Token
        Token::delete($this->_token);
        //注销成功的事件
        Hook::listen("user_logout_successed", $this->_user);
        return true;
    }

    /**
     * 修改密码
     * @param string    $new        新密码
     * @param string    $old        旧密码
     * @param bool      $ignore  忽略旧密码
     * @return boolean
     */
    public function changePassword($new, $old = '', $ignore = false)
    {
        if (!$this->_logined) {
            $this->setError('You are not logged in');
            return false;
        }
        //判断旧密码是否正确
        if ($this->_user['password'] == get_encrypt_password($old, $this->_user['salt']) || $ignore) {
            $salt = Random::alnum();
            $new = get_encrypt_password($new, $salt);
            $data = [
                'password' => $new,
                'salt' => $salt,
                'update_time' => time()
            ];
            Db::table($this->config['auth_user'])->where('id', $this->_user['id'])->update($data);

            Token::delete($this->_token);
            //修改密码成功的事件
            Hook::listen("user_changepwd_successed", $this->_user);
            return true;
        } else {
            $this->setError('Password is incorrect');
            return false;
        }
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = Db::table($this->config['auth_user'])->where('id', $user_id)->find();
        if (!$user) {
            return false;
        }
        // 调用事务删除账号
        $result = Db::transaction(function ($db) use ($user_id) {
            // 删除会员
            Db::table($this->config['auth_user'])->delete($user_id);
            // 删除会员指定的所有Token
            Token::clear($user_id);
            return true;
        });
        if ($result) {
            Hook::listen("user_delete_successed", $user);
        }
        return $result ? true : false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower(request()->action()), $arr) || in_array('*', $arr)) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }

}