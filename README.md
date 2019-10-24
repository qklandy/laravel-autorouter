# Laravel/Lumen AutoRouter
Laravel/Lumen 注解路由自动加载和中间件自动注入

## Table of Contents

1. [How](#how)
1. [Usage](#Usage)

### How

#### Install with Composer:

```
composer require qklin/laravel-auto-router
```

#### Use simply:

```
# add autorouter middleware
$app->routeMiddleware([
    //...

    'api_throttle' => \Qikl\AutoRouter\Middleware\ApiThrottleMiddleware::class,
    'token'        => \Qikl\AutoRouter\Middleware\TokenMiddleware::class,
    'check_sign'   => \Qikl\AutoRouter\Middleware\CheckSignMiddleware::class,
    'validate'     => \Qikl\AutoRouter\Middleware\ValidateMiddleware::class,
    'inside'       => \Qikl\AutoRouter\Middleware\InsideMiddleware::class,
]);

# add provider
$app->register(Qklin\AutoRouter\AppServiceProvider::class);
```

### Usage
#### 目前支持的注解
1. arRouter: 可直接配置方法携带后缀控制中间件
1. arMethod: POST|GET|PUT|...
1. arOnlyInisde: 路由必须inside开头

#### 路由自动注入使用
1. api必须【bapi】前缀。like：HotKeys => hot_keys
1. 路径包含驼峰目录以【_】分隔。like：HotKeys => hot_keys
1. 方法包含【.】的目录以【_】分隔。like：V1.0 => v1_0
1. 方法包含驼峰以【-】分隔。like：getListsO => get-list-o


#### 控制器方法添加注解配置案例
```
具体案例, 模块根目录和控制器文件：app/Modules/Module/Hotkeys/V1.0/Controllers/IndexController.php
方法：getList，注解wptRouter getListO
路由：/m/module/hot-keys/v1_0/index/get-list-o

/**
 * 案例一 纯路由注解
 * 匹配：/m/module/articles/college/detail
 * @wptRouter
 * @return string
 */
public function detail()
{
}
 
/**
 * 案例二：路由注解并配置路由地址方法
 * 匹配：/m/module/articles/college/detail-o
 * @wptRouter detailO
 * @return string
 */
public function detail()
{
}
 
 
/**
 * 案例三：路由注解请求方法和开启内网注解
 * 匹配，且前缀必须inside开头：/inside/module/articles/college/detail-o
 * 只支持post和get请求方法
 * @wptRouter detailO
 * @wptMehtod POST|GET
 * @wptOnlyInside
 * @return string
 */
public function detail()
{
}
```

#### wptRouter注解配置说明
1. O:Operate   操作 自动注入节流中间件
2. L:Look      查看 自动移除节流中间件[如果存在]
3. I:Inside    内网 自动注入内网中间件，且放置最前[如果不存在]，自动剔除token中间件
4. N:NotAuth   无需授权，公开api，自动移除token中间件[如果存在]
5. V:validate  请求验证，自动移除请求验证中间件[如果不存在]
6. X:checkSign 请求验签
6. 如果都不存在，默认根据route.php配置走

#### 配置route.conf
```

// 文件位置：app/config/route.php
// 本文件可配置可不配置，根据需求配置
return [
    "middleware" => [  //中间件，目前只支持
        "controllers" => [ // 路由控制器路径 => 中间件
            "bapi/wmapi/articles/college" => ["token", "validate"],
        ],
        "actions"     => [ // 控制器方法 => 中间件
        ]
    ]
];
```