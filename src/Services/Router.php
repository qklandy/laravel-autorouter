<?php

namespace Qklin\AutoRouter\Services;

use Exception;

class Router
{
    /**
     * 自动注入和移除相关的中间件
     * O:Operate操作；  L:look 查看；        N:NotAuth 无需授权，公开api；
     * I:标识内网请求；  V:validate 请求验证；
     * I:会自动移除token授权中间件
     * @param $controllerInfo
     * @param $reqMethod
     * @throws Exception
     */
    public function addRoute($controllerInfo, $reqMethod)
    {
        $app = app();
        $routers = $app->router->getRoutes();
        if (!is_array($routers)) {
            $routers = $routers->getRoutes();
        }

        // 默认支持get和post
        $method = strtoupper($reqMethod);
        $supportMethod = ['get', 'post'];

        // 判断方式是否支持
        if (in_array($method, $supportMethod)) {
            throw new Exception("无法支持的请求方法", -113);
        }

        // 如果已经通过route.php配置路由了，直接跳过不处理
        $uri = '/' . trim($controllerInfo['uri'], '/');
        if (isset($routers[$reqMethod . $uri])) {
            return;
        }

        // 找不到对应的类，抛出错误
        if (!class_exists($controllerInfo['class'])) {

            $errMsg = "无法匹配新路由规则模式，找不到类: "
                . "[{$controllerInfo['class']}]；json: " . qklin_json_encode($controllerInfo);
            if (env('APP_ENV') == 'PROD') {
                $errMsg = "无法匹配新路由规则模式";
            }
            throw new Exception($errMsg, -110);
        }

        // 判断是否允许访问的控制器方法
        if (!$this->hasReflectionMethod($controllerInfo)) {

            $errMsg = "该方法未被允许访问: [{$controllerInfo['class']}::{$controllerInfo['action']}]";
            if (env('APP_ENV') == 'PROD') {
                $errMsg = "该方法未被允许访问: [{$controllerInfo['action']}]";
            }
            throw new Exception($errMsg, -111);
        }

        // 处理请求方法
        if (!empty($controllerInfo['ar_method'])) {
            $supportMethod = $controllerInfo['ar_method'];
        }

        // 判断方式是否支持
        if (!in_array(strtolower($method), $supportMethod)) {
            throw new Exception("无法支持的请求方法", -113);
        }

        // 中断
        $this->interrupt($controllerInfo);

        // 加载route配置
        $app->configure('route');

        // 获取自动路由相关配置
        $routeConfig = $app->make('config')->get('route');

        // 获取中间件等属性
        $routeAttributes = [
            'middleware' => env('AUTOROUTER_DEFAULT_MIDDLEWARE', '')
                ? explode(",", env('AUTOROUTER_DEFAULT_MIDDLEWARE', ''))
                : [] //默认中间件
        ];

        // 控制器路由中间件
        if (isset($routeConfig['middleware']['controllers'][$controllerInfo['ctl']])) {
            $routeAttributes['middleware'] = array_merge(
                $routeAttributes['middleware'],
                $routeConfig['middleware']['controllers'][$controllerInfo['ctl']]
            );
        }

        // 方法路由中间件
        if (isset($routeConfig['middleware']['actions'][$controllerInfo['uri']])) {
            $routeAttributes['middleware'] = array_merge(
                $routeAttributes['middleware'],
                $routeConfig['middleware']['actions'][$controllerInfo['uri']]
            );
        }

        // 移除可能的重复
        if (!empty($routeAttributes['middleware'])) {
            $routeAttributes['middleware'] = array_values(array_unique($routeAttributes['middleware']));
        }

        // 自动解析处理中间件
        $this->injectMiddleWare($routeAttributes, $controllerInfo);

        // 注册路由
        $app->router->group($routeAttributes, function ($router) use ($controllerInfo, $method) {
            $router->$method($controllerInfo['uri'], $controllerInfo['class'] . "@" . $controllerInfo['action']);
        });
    }

    /**
     * 特殊的中断异常
     * @param $controllerInfo
     * @throws Exception
     */
    protected function interrupt($controllerInfo)
    {
        // 如果是只能内网注解，请以inside开头
        if (($controllerInfo['ar_only_inside'] && $controllerInfo['prefix'] != 'inside')
            || ($controllerInfo['prefix'] == 'inside' && !$controllerInfo['ar_only_inside'])) {

            $errMsg = "该方法开启了内网，请使用inside前缀 或 使用了inside前缀，必须开启内网: "
                . "[{$controllerInfo['class']}::{$controllerInfo['action']}]；json: " . qklin_json_encode($controllerInfo);
            if (env('APP_ENV') == 'PROD') {
                $errMsg = "该方法开启了内网，请使用inside前缀 或 使用了inside前缀，必须开启内网: [{$controllerInfo['action']}]";
            }
            throw new Exception($errMsg, 114);
        }
    }

    /**
     * 反射处理
     * @param $controllerInfo
     * @return bool
     * @throws \ReflectionException
     */
    protected function hasReflectionMethod(&$controllerInfo)
    {
        $class = $controllerInfo['class'];
        $action = $controllerInfo['action'];

        $hasMatch = false;
        $reflection = new \ReflectionClass($class);
        foreach ($reflection->getMethods() as $refMethod) {

            // 不是公有方法, 直接跳过
            if (!$refMethod->isPublic()) {
                continue;
            }

            // 获取注释参数
            $docComment = $refMethod->getDocComment();

            // 无注解直接跳过
            if (!$docComment) {
                continue;
            }

            $annotateService = app('autorouter.annotate');
            $docParams = $annotateService->parseLines($docComment)->parseSimple();
            $arRouter = $annotateService->getDocVar('router');

            if (isset($docParams[$arRouter])) {

                // 路由请求的真正方法
                if ($docParams[$arRouter] && $docParams[$arRouter] == $action) {
                    $controllerInfo['router_action'] = $docParams[$arRouter];
                    $controllerInfo['action'] = $refMethod->name;

                    $hasMatch = true;
                }

                if (!$docParams[$arRouter] && $refMethod->name == $action) {
                    $controllerInfo['router_action'] = $refMethod->name;

                    $hasMatch = true;
                }

                if ($hasMatch) {
                    $arMethod = $annotateService->getDocVar('method');
                    $arOnlyInside = $annotateService->getDocVar('only_inside');
                    $controllerInfo['ar_method'] = isset($docParams[$arMethod])
                        ? ($docParams[$arMethod]
                            ? explode("|", strtolower($docParams[$arMethod]))
                            : [])
                        : [];
                    $controllerInfo['ar_only_inside'] = isset($docParams[$arOnlyInside]) ? 1 : 0;
                    break;
                }
            }
        }

        return $hasMatch;
    }

    protected function injectMiddleWare(&$routeAttributes, $controllerInfo)
    {
        if (!app()->bound('autorouter.middleware')) {
            throw new Exception("请注册中间件", -115);
        }

        app('autorouter.middleware')->inject($routeAttributes, $controllerInfo, env('AUTOROUTER_MIDDLEWARE_SUFFIX', 'OLNVIX'));
    }

    public function controller($pathInfo, $pathInfoArr)
    {
        $pathInfoArr = array_map(function ($v) {

            // [.]转换成[_]
            $tmp = ucfirst(str_replace(".", "_", $v));

            // 兼容除方法外的驼峰目录: [m/h/inside]/live-video/live/do-it -> LiveVideo/Live/doIt
            $tmp = preg_replace_callback("/(\-[^-]+)/", function ($matches) {
                return ucfirst(substr($matches[0], 1));
            }, $tmp);

            return $tmp;

        }, $pathInfoArr);

        // 移除结尾
        $action = lcfirst(array_splice($pathInfoArr, -1)[0]);

        // 控制器路由，用于配置里取中间件
        $pathController = substr($pathInfo, 0, strrpos($pathInfo, "/"));

        // 移除开头
        $prefix = strtolower(array_splice($pathInfoArr, 0, 1)[0]);

        // 获取控制器类
        // app/http目录下
        if ($prefix == env('LARAVEL_ORIGIN_HTTP_PREFIX', 'h')) {
            $ctlClass = "\\App\\Http\\Controllers\\" . implode("\\", $pathInfoArr) . "Controller";
        }
        // 自定义的结构模块目录下，默认App\Modules
        if (in_array($prefix, explode(",", env('AUTOROUTER_MODULE_HTTP_PREFIX', 'm,inside')))) {
            $moduleName = $pathInfoArr[sizeof($pathInfoArr) - 2];
            $ctlName = $pathInfoArr[sizeof($pathInfoArr) - 1];
            array_splice($pathInfoArr, -2, 2, [$moduleName, "Controllers", $ctlName . "Controller"]);
            $ctlClass = "\\App\\" . env('AUTO_ROUTER_MODULE_DIR', 'Modules') . "\\" . implode("\\", $pathInfoArr);
        }

        return [
            'uri'    => $pathInfo, //pathinfo
            'ctl'    => $pathController, //pathinfo.controller
            'prefix' => $prefix, //前缀
            'class'  => $ctlClass, //实际控制器类
            'action' => $action //方法
        ];
    }

    /**
     * 判断是否支持pathinfo
     * @throws Exception
     */
    public function hasSupportPathInfo()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (!isset($requestUri)) {
            throw new Exception("无法自动匹配pathinfo，请手动配置路由规则", -109);
        }
    }

    /**
     * @throws Exception
     */
    public function parseUritoArr()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (!$requestUri || $requestUri == "/") {
            return [];
        }

        return explode("?", $requestUri);
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function getRequestMethod()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? "");
        if (!$method) {
            // HEAD add: METHOD-OVERRIDE
            $method = strtoupper($_SERVER['HTTP_METHOD_OVERRIDE'] ?? "");
        }

        // 如果是模拟的表单，可参数_method, 再做一次判断
        if ($method == 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        return $method;
    }
}
