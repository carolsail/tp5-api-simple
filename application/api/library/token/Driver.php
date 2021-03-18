<?php

namespace app\api\library\token;

abstract class Driver{
	protected $handler = null;

	/**
     * 存储Token
     * @param   string $token Token
     * @param   int $auth_id 会员ID
     * @param   int $expire 过期时长,0表示无限,单位秒
     * @return bool
     */
    abstract function set($token, $auth_id, $expire = 0);

    /**
     * 刷新Token过期时间
     * @param   string $token Token
     * @param   int $expire 过期时长,0表示无限,单位秒
     * @return  array
     */
    abstract function refresh($token, $expire = 0);

    /**
     * 获取Token内的信息
     * @param   string $token
     * @return  array
     */
    abstract function get($token);

    /**
     * 判断Token是否可用
     * @param   string $token Token
     * @param   int $auth_id 会员ID
     * @return  boolean
     */
    abstract function check($token, $auth_id);

    /**
     * 删除Token
     * @param   string $token
     * @return  boolean
     */
    abstract function delete($token);

    /**
     * 删除指定用户的所有Token
     * @param   int $auth_id
     * @return  boolean
     */
    abstract function clear($auth_id);

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * 获取加密后的Token
     * @param string $token Token标识
     * @return string
     */
    protected function getEncryptedToken($token)
    {
        $config = config('token.');
        return hash_hmac($config['hashalgo'], $token, $config['key']);
    }

    /**
     * 获取过期剩余时长
     * @param $expireTime
     * @return float|int|mixed
     */
    protected function getExpiredIn($expireTime)
    {
        return $expireTime ? max(0, $expireTime - time()) : 365 * 86400;
    }
}