# tp5-api-simple

> 注意：api simple版，单纯封装了多语言响应，全局异常监听，验证器等，运行环境要求PHP5.6以上


## 安装启动

+ composer install


## php think

1. 生成控制器类, 默认继承\think\Controller
```
php think make:controller api/Test --plain
php think make:controller api/Test --api
```
2. 生成模型类, 默认继承\think\Model
```
php think make:model api/Test
```
3. 生成验证类, 默认继承\think\Validate
```
php think make:validate api/Test
```
4. 生成中间件
```
php think make:middleware Test
```
5. 启动内置服务器
```
php think run
```
6. 清除缓存
```
php think clear
php think clear --log
php think clear --cache
php think clear --route
```
7. 生成路由映射缓存, runtime目录下生成route.php
```
php think optimize:route
```