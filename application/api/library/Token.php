<?php

namespace app\api\library;

class Token {

    /**
     * @var array Token的实例
     */
    public static $instance = [];

    /**
     * @var object 操作句柄
     */
    public static $handler;

	/**
     * 连接Token驱动
     * @access public
     * @param  array       $options 配置数组
     * @param  bool|string $name    Token连接标识 true 强制重新连接
     * @return Driver
     */
    public static function connect($options = [], $name = false)
    {
        $type = !empty($options['type']) ? $options['type'] : 'Mysql';

        if (false === $name) {
            $name = md5(serialize($options));
        }

        if (true === $name || !isset(self::$instance[$name])) {
            $class = false === strpos($type, '\\') ? '\\app\\api\\library\\token\\driver\\' . ucwords($type) : $type;

            if (true === $name) {
                return new $class($options);
            }

            self::$instance[$name] = new $class($options);
        }

        return self::$instance[$name];
    }

    /**
     * 自动初始化Token
     * @access public
     * @param  array $options 配置数组
     * @return Driver
     */
    public static function init(array $options = [])
    {
        if (is_null(self::$handler)) {
        	$config = config('token.');

        	if (empty($options)) {
                $options = $config;
            }else{
            	$options = array_merge($config, $options);
            }

            self::$handler = self::connect($options);
        }
        return self::$handler;
    }

    /**
     * 判断Token是否可用
     * @param string $token Token标识
     * @return bool
     */
    public static function check($token, $auth_id)
    {
        return self::init()->check($token, $auth_id);
    }

    /**
     * 读取Token
     * @access public
     * @param  string $token Token标识
     * @param  mixed $default 默认值
     * @return mixed
     */
    public static function get($token, $default = false)
    {
        return self::init()->get($token, $default);
    }

    /**
     * 写入Token
     * @access public
     * @param  string $token Token标识
     * @param  mixed $auth_id 存储数据
     * @param  int|null $expire 有效时间 0为永久
     * @return boolean
     */
    public static function set($token, $auth_id, $expire = 0)
    {
        return self::init()->set($token, $auth_id, $expire);
    }

    /**
     * 刷新token过期时间
     * @access public
     * @param  string $token Token标识
     * @param  int|null $expire 有效时间 0为永久
     * @return boolean
     */
    public static function refresh($token, $expire = 0){
        return self::init()->refresh($token, $expire);
    }

    /**
     * 删除Token
     * @param string $token 标签名
     * @return bool
     */
    public static function delete($token)
    {
        return self::init()->delete($token);
    }

    /**
     * 清除Token
     * @access public
     * @param  string $token Token标记
     * @return boolean
     */
    public static function clear($auth_id = null)
    {
        return self::init()->clear($auth_id);
    }
}