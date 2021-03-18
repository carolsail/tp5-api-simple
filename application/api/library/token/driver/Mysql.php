<?php

namespace app\api\library\token\driver;

use app\api\library\token\Driver;

class Mysql extends Driver {

    /**
     * 默认配置
     * @var array
     */
    protected $options = [
        'table'      => 'user_token',
        'expire'     => 0,
        'connection' => [],
    ];

	/**
     * 构造函数
     * @param array $options 参数
     * @access public
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if ($this->options['connection']) {
            $this->handler = \think\Db::connect($this->options['connection'])->name($this->options['table']);
        } else {
            $this->handler = \think\Db::name($this->options['table']);
        }
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
        $expire = intval($expire);
        $expireTime = !is_null($expire) && $expire !== 0 ? time() + $expire : 0;
        $token = $this->getEncryptedToken($token);
        $this->handler->insert(['token' => $token, 'auth_id' => $auth_id, 'create_time' => time(), 'expire_time' => $expireTime]);
        return TRUE;
    }

    /**
     * 刷新token
     */
    public function refresh($token, $expire = 0){
        $expire = intval($expire);
        $expireTime = !is_null($expire) && $expire !== 0 ? time() + $expire : 0;
        $tokenInfo = $this->get($token);
        if($tokenInfo){
            $tokenInfo['expire_time'] = $expireTime;
            $tokenInfo['expires_in'] = $this->getExpiredIn($expireTime);
            $data = [
                'expire_time' => $expireTime,
                'token' => $this->getEncryptedToken($token)
            ];
            $this->handler->where('token', $this->getEncryptedToken($token))->update($data);
        }
        return $tokenInfo;
    }

    /**
     * 获取Token内的信息
     * @param   string $token
     * @return  array
     */
    public function get($token)
    {
        $data = $this->handler->where('token', $this->getEncryptedToken($token))->find();
        if ($data) {
            if (!$data['expire_time'] || $data['expire_time'] > time()) {
                //返回未加密的token给客户端使用
                $data['token'] = $token;
                //返回剩余有效时间
                $data['expires_in'] = $this->getExpiredIn($data['expire_time']);
                return $data;
            } else {
                self::delete($token);
            }
        }
        return [];
    }

    /**
     * 判断Token是否可用
     * @param   string $token Token
     * @param   int $auth_id 会员ID
     * @return  boolean
     */
    public function check($token, $auth_id)
    {
        $data = $this->get($token);
        return $data && $data['auth_id'] == $auth_id ? true : false;
    }

    /**
     * 删除Token
     * @param   string $token
     * @return  boolean
     */
    public function delete($token)
    {
        $this->handler->where('token', $this->getEncryptedToken($token))->delete();
        return true;
    }

    /**
     * 删除指定用户的所有Token
     * @param   int $auth_id
     * @return  boolean
     */
    public function clear($auth_id)
    {
        $this->handler->where('auth_id', $auth_id)->delete();
        return true;
    }
}