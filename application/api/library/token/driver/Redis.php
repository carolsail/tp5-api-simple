<?php

namespace app\api\library\token\driver;

use app\api\library\token\Driver;

class Redis extends Driver {
	protected $options = [
        'host'        => '127.0.0.1',
        'port'        => 6379,
        'password'    => '',
        'select'      => 0,
        'timeout'     => 0,
        'expire'      => 0,
        'persistent'  => false,
        'userprefix'  => 'up:',
        'tokenprefix' => 'tp:',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @throws \BadFunctionCallException
     * @access public
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->handler = new \Redis;
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 获取加密后的Token
     * @param string $token Token标识
     * @return string
     */
    protected function getEncryptedToken($token)
    {
        return $this->options['tokenprefix'] . parent::getEncryptedToken($token);
    }

    /**
     * 获取会员的key
     * @param $auth_id
     * @return string
     */
    protected function getUserKey($auth_id)
    {
        return $this->options['userprefix'] . $auth_id;
    }

    /**
     * 存储Token
     * @param   string $token Token
     * @param   int $auth_id 会员ID
     * @param   int $expire 过期时长,0表示无限,单位秒
     * @return bool
     */
    public function set($token, $auth_id, $expire = 0)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        $key = $this->getEncryptedToken($token);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $auth_id);
        } else {
            $result = $this->handler->set($key, $auth_id);
        }
        //写入会员关联的token
        $this->handler->sAdd($this->getUserKey($auth_id), $key);
        return $result;
    }

    /**
     * 刷新token
     */
    public function refresh($token, $expire = 0){
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        $key = $this->getEncryptedToken($token);
        if ($expire) {
            $result = $this->handler->expire($key, $expire);
        }
        return $this->get($token);
    }

    /**
     * 获取Token内的信息
     * @param   string $token
     * @return  array
     */
    public function get($token)
    {
        $key = $this->getEncryptedToken($token);
        $value = $this->handler->get($key);
        if (is_null($value) || false === $value) {
            return [];
        }
        //获取有效期
        $expire = $this->handler->ttl($key);
        $expire = $expire < 0 ? 365 * 86400 : $expire;
        $expireTime = time() + $expire;
        $result = ['token' => $token, 'auth_id' => $value, 'expire_time' => $expireTime, 'expired_in' => $expire];

        return $result;
    }

    /**
     * 判断Token是否可用
     * @param   string $token Token
     * @param   int $auth_id 会员ID
     * @return  boolean
     */
    public function check($token, $auth_id)
    {
        $data = self::get($token);
        return $data && $data['auth_id'] == $auth_id ? true : false;
    }

    /**
     * 删除Token
     * @param   string $token
     * @return  boolean
     */
    public function delete($token)
    {
        $data = $this->get($token);
        if ($data) {
            $key = $this->getEncryptedToken($token);
            $auth_id = $data['auth_id'];
            $this->handler->del($key);
            $this->handler->sRem($this->getUserKey($auth_id), $key);
        }
        return true;

    }

    /**
     * 删除指定用户的所有Token
     * @param   int $auth_id
     * @return  boolean
     */
    public function clear($auth_id)
    {
        $keys = $this->handler->sMembers($this->getUserKey($auth_id));
        $this->handler->del($this->getUserKey($auth_id));
        $this->handler->del($keys);
        return true;
    }
}