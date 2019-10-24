<?php

namespace Qklin\AutoRouter\Middleware;

use App\Services\UserDDInfoService;
use Closure;

class TokenMiddleware
{
    /**
     * 权限TOKEN认证
     * @param         $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 检测权限
        $flag = app('autorouter.middleware.token')->check($request);

        // token认证成功，继续执行; 认证失败，401无权限
        return $flag ? $next($request) : response("Token Authentication failure", 401);
    }

}
