<?php

namespace Qklin\AutoRouter\Middleware;

use App\Http\Requests\ResultRequest;
use App\Utils\CommonUtil;
use Closure;

class ValidateMiddleware
{
    /**
     * 入参验证
     * @param         $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        $uses = $route[1]["uses"] ?? "";
        if (empty($uses)) {
            // 路由解析异常
            return response("", 404);
        }
        list($controller, $action) = explode('@', $uses);
        $requestValidate = str_replace(["Controllers", "Controller"], ["Requests", "Request"], $controller);
        if (class_exists($requestValidate) && method_exists($requestValidate, $action)) {
            $resultValidate = (new $requestValidate)->$action($request);
            if ($resultValidate instanceof ResultRequest) {
                if ($resultValidate->result === true) {
                    return $next($request);
                } else {
                    return !empty($resultValidate->data)
                        ? response($resultValidate->data['code'], $resultValidate->data['msg'])
                        : response("Verification failure, But no return parameters are set", 503);
                }
            } else {
                // 接口设置对应的验证请求类和方法的返回值非ResultRequest类，503服务不可用
                return response("Verification request return error !", 503);
            }
        } else {
            // 接口没有设置对应的验证请求类和方法，503服务不可用
            return response("The server does not have a validation for the request !", 503);
        }

    }

}