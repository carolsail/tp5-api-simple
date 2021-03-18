<?php

return [
	'host'        => Env::get('redis.host', 'localhost'),
	'password'    => Env::get('redis.password', ''),
	'port'        => Env::get('redis.port', '6379')
];