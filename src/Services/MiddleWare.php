<?php

namespace Qklin\AutoRouter\Services;

use Exception;

class MiddleWare
{
    /**
     * 注入中间件
     * @param        $routeAttributes
     * @param        $controllerInfo
     * @param string $rejectSuffixs
     * @throws Exception
     */
    public function inject(&$routeAttributes, $controllerInfo, $rejectSuffixs = "OLNVIX")
    {
        preg_match("#[" . $rejectSuffixs . "]+$#", $controllerInfo['router_action'], $suffixs);

        // 无法匹配路由后缀中间件配置且无唯一内网标识
        if (empty($suffixs) && !$controllerInfo['ar_only_inside']) {
            return;
        }

        // 获取后缀数组
        $suffixsArr = !empty($suffixs) ? str_split($suffixs[0]) : [];

        if (in_array('O', $suffixsArr, true) && in_array('L', $suffixsArr, true)) {
            $errMsg = "该方法控制不能同时存在O和L: [{$controllerInfo['class']}::{$controllerInfo['router_action']}]";
            if (env('APP_ENV') == 'PROD') {
                $errMsg = "该方法控制不能同时存在O和L: [{$controllerInfo['router_action']}]";
            }
            throw new Exception($errMsg, -112);
        }

        $sufixArr = $rejectSuffixs ? str_split($rejectSuffixs) : [];
        if (!empty($sufixArr)) {
            foreach ($sufixArr as $suffix) {
                $injectAction = "inject" . $suffix;
                if (method_exists($this, $injectAction)) {
                    $this->$injectAction($suffixsArr, $routeAttributes, $controllerInfo);
                }
            }

            // 移除可能的重复
            if (!empty($routeAttributes['middleware'])) {
                $routeAttributes['middleware'] = array_values(array_unique($routeAttributes['middleware']));
            }
        }
    }

    public function injectO($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[O]自动注入节流中间件
        if (in_array('O', $suffixsArr, true)) {

            // 判断是否已经注入节流中间件
            $hasApiThrottle = false;
            if (!empty($routeAttributes['middleware'])) {
                foreach ($routeAttributes['middleware'] as $mware) {
                    if (strpos($mware, 'api_throttle') !== false) {
                        $hasApiThrottle = true;
                        break;
                    }
                }
            }

            // 无中间件 自动注入节流中间件
            if (!$hasApiThrottle) {
                $routeAttributes['middleware'] = array_merge($routeAttributes['middleware'], ['api_throttle']);
            }
        }
    }

    public function injectL($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[L]标识无需节流，如果配置了，自动移除
        if (in_array('L', $suffixsArr, true)) {

            // 自动移除节流中间件
            if (!empty($routeAttributes['middleware'])) {
                foreach ($routeAttributes['middleware'] as $mware) {
                    if (strpos($mware, 'api_throttle') !== false) {
                        array_splice($routeAttributes['middleware'], array_search($mware, $routeAttributes['middleware']), 1);
                    }
                }
            }
        }
    }

    public function injectN($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[N]标识无需授权，如果配置了，自动去除
        if (in_array('N', $suffixsArr, true)) {

            // 移除授权
            if (!empty($routeAttributes['middleware'])) {
                if (in_array('token', $routeAttributes['middleware'])) {
                    array_splice($routeAttributes['middleware'], array_search('token', $routeAttributes['middleware']), 1);
                }
            }
        }
    }

    public function injectV($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[v]标识需要验证请求
        if (in_array('V', $suffixsArr, true)) {

            // 自动注入验证请求中间件
            if (!empty($routeAttributes['middleware'])) {
                if (!in_array('validate', $routeAttributes['middleware'])) {
                    array_unshift($routeAttributes['middleware'], 'validate');
                }
            } else {
                $routeAttributes['middleware'] = ['validate'];
            }
        }
    }

    public function injectI($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[I]标识内网请求, 自动注入节流中间件
        // 如果ar_only_inside=1开头自动注入inside
        if (in_array('I', $suffixsArr, true) || $controllerInfo['ar_only_inside']) {

            // 自动注入内网中间件
            if (!empty($routeAttributes['middleware'])) {
                if (!in_array('inside', $routeAttributes['middleware'])) {
                    array_unshift($routeAttributes['middleware'], 'inside');
                }
            } else {
                $routeAttributes['middleware'] = ['inside'];
            }

            // 2019-09-10 14:28 qklin 根据目前情况，内外肯定不走token，直接移除授权
            if (in_array('token', $routeAttributes['middleware'])) {
                array_splice($routeAttributes['middleware'], array_search('token', $routeAttributes['middleware']), 1);
            }
        }
    }

    public function injectX($suffixsArr, &$routeAttributes, &$controllerInfo)
    {
        // 结尾为英文：[X]标识需要验签
        if (in_array('X', $suffixsArr, true)) {

            // 自动注入验签中间件
            if (!empty($routeAttributes['middleware'])) {
                if (!in_array('check_sign', $routeAttributes['middleware'])) {
                    array_unshift($routeAttributes['middleware'], 'check_sign');
                }
            } else {
                $routeAttributes['middleware'] = ['check_sign'];
            }
        }
    }
}