<?php

namespace Qklin\AutoRouter\Services;

use Exception;

class AutoRouter
{
    /**
     * 自动注入路由
     */
    public function handle()
    {
        if (PHP_SAPI == 'cli') {
            return;
        }

        $app = app();

        if (!$app->bound('autorouter.router')) {
            throw new Exception("请先注册自动路由", -100);
        }

        $router = app('autorouter.router');

        // 判断是否支持pathinfo
        $router->hasSupportPathInfo();

        // 请求方法判断
        $reqMethod = $router->getRequestMethod();
        if (!$reqMethod) {
            throw new Exception("无法识别的请求方法", -108);
        }

        $urlArr = $router->parseUritoArr();
        if (empty($urlArr)) {
            return;
        }

        // 记录日志
        $router->reqLog($urlArr, $reqMethod);

        // pathinfo 移除第一个斜杆
        $pathInfo = substr($urlArr[0], 1);

        // 转换路径
        $pathInfoArr = explode("/", $pathInfo);

        // 如果转化路由小于3，直接返回，prefix/[module]/ctl/action
        if (sizeof($pathInfoArr) < 3) {
            return;
        }

        // 获取路由解析控制器信息
        $controllerInfo = $router->controller($pathInfo, $pathInfoArr);

        // 只处理新路由前缀 可兼容原本的路由配置
        $prefixs = explode(
            ",",
            env('LARAVEL_ORIGIN_HTTP_PREFIX', 'h') . "," . env('AUTOROUTER_MODULE_HTTP_PREFIX', 'm,inside')
        );
        if (in_array($controllerInfo['prefix'], $prefixs)) {
            $router->addRoute($controllerInfo, $reqMethod);
        }
    }
}