<?php

namespace Qklin\AutoRouter\Middleware;

use Closure;
use Illuminate\Http\Request;

class InsideMiddleware
{
    /**
     * 内网域名鉴权
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 非正式环境允许不使用内网域名
        if (env('APP_ENV') != 'PROD') {
            return $next($request);
        }

        // 正式环境判断请求域名是否为内网域名
        $requestHost = $request->getHost();
        $configHosts = env('AR_INSIDE_HOSTS');
        $insideHosts = explode(",", $configHosts);

        // 验证
        if (!in_array($requestHost, $insideHosts)) {
            if (app()->bound('Psr\Log\LoggerInterface')) {
                app('Psr\Log\LoggerInterface')->info('内网域名鉴权失败', [
                    'url'         => $request->fullUrl(),
                    'requestHost' => $requestHost,
                    'configHosts' => $configHosts
                ]);
            }
            return response("Inside Authentication failure", 401);
        }
        return $next($request);
    }
}