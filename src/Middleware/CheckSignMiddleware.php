<?php

namespace Qklin\AutoRouter\Middleware;

use App\Facades\Logger;
use Closure;

class CheckSignMiddleware
{

    /**
     * 请求验签
     * @param         $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $delFields = ['s', 'sign', 'power_particle_arr'];
        $reqDdata = $reqLogData = $request->all();

        if (!isset($reqDdata['timestamp'])) {
            return response("Check Sign Authentication failure, not has timestamp params", 401);
        }

        // 判断是否超时，超时返回失败
        $timeOut = (int)env('AR_CHECK_SIGN_TIMEOUT', 10);
        if ((int)$reqDdata['timestamp'] < time() - $timeOut) {
            if (app()->bound('autorouter.logger')) {
                app('autorouter.logger')->arLog([
                    'position' => 'checksign',
                    'msg'      => '验签失败: 超时',
                    'params'   => [
                        'reqData' => $reqLogData,
                        'time'    => time(),
                        'reqTime' => $reqDdata['timestamp']
                    ]
                ]);
            }
            return response("Check Sign Authentication failure, timestamp has timeout", 401);
        }

        $reqSign = $reqDdata['sign'];

        // 删除相关laravel可能携带的参数
        foreach ($delFields as $field) {
            if (isset($reqDdata[$field])) {
                unset($reqDdata[$field]);
            }
        }

        // 生成sign
        $key = env('AR_CHECK_SIGN_KEY', '');
        ksort($reqDdata);
        $waitStr = http_build_query($reqDdata) . "&key=" . $key;
        $sign = hash_hmac("sha256", $waitStr, $key);

        $checkRes = $sign == $reqSign ? true : false;

        if (!$checkRes) {
            if (app()->bound('autorouter.logger')) {
                app('autorouter.logger')->arLog([
                    'position' => 'checksign',
                    'msg'      => '验签失败: 不通过',
                    'params'   => [
                        'reqData'  => $reqLogData,
                        'signData' => $reqDdata,
                        'sign'     => $sign,
                        'reqSign'  => $reqSign
                    ]
                ]);
            }
        }

        // sign认证失败，401无权限
        return $checkRes ? $next($request) : response("Check Sign Authentication failure", 401);
    }

}