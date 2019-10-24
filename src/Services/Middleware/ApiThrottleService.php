<?php
/**
 * api间隙请求限流demo
 */

namespace Qklin\AutoRouter\Services\Middleware;

use Exception;
use Illuminate\Http\Request;

/**
 * Class ApiThrottleService
 * @package App\Http\Services
 * @property LockCache $cache
 */
class ApiThrottleService
{
    public function __construct()
    {
        $this->cache = app('autorouter.middleware.api.cache');
    }

    /**
     * 执行节流结果
     * @param Request $request
     * @param int     $ttl
     * @return bool false:不需要节流; true:需要节流
     * @throws \Throwable
     */
    public function check(Request $request, $ttl = 5): bool
    {
        $lockType = env('AR_API_THROTTLE', 'ar_api_throttle');

        $lockKey = $this->buildKey($request);

        // setnx上锁 排它锁
        $hadLock = $this->cache->inLock($lockType, $lockKey);
        if ($hadLock) { //上锁成功, 不需要节流
            return false;
        }

        //抛出异常,报错
        throw new Exception("触发保护机制, 请在{$ttl}秒后再操作", -110);
        return true;
    }

    /**
     * 生成key
     * @param $request
     * @return bool|string
     */
    private function buildKey($request)
    {
        $params = $request->all();

        // 过滤不能用作加密的参数
        $removeFilter = ['power', 'token', 'time', 'power_particle_arr'];
        foreach ($params as $key => $value) {
            if (in_array($key, $removeFilter)) {
                unset($params[$key]);
            }
        }

        // 去掉钉钉参数,验证是否是空数组
        $tmpArr = $params;
        $ddFilter = ['ddid', 'ddname', 'ddphone'];
        foreach ($tmpArr as $key => $value) {
            if (in_array($key, $ddFilter)) {
                unset($tmpArr[$key]);
            }
        }

        // 如果去掉钉钉参数后,空数组不需要节流
        if (empty($tmpArr)) {
            return false;
        }

        // 获取route
        $pathInfo = $request->getPathInfo();

        // 重新排序,防止参数错乱导致生成新的lockKey
        sort($params);

        // 生成lockKey
        return sha1($pathInfo . json_encode($params));
    }
}