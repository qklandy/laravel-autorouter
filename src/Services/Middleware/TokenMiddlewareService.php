<?php
/**
 * token的demo
 */

namespace Qklin\AutoRouter\Services\Middleware;

use App\Libraries\writelog\WriteLog;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TokenMiddlewareService
{
    /**
     * token验证
     * @param Request $request
     * @return bool
     */
    public function check(Request $request): bool
    {
        //本地开发环境不验证token
        if (config('app.env') === "DEV") {
            return true;
        }

        // token 验证
        $token = strtolower($request->input('token', ''));
        $power = $request->input('power', '');
        $time = $request->input('time', '');

        try {
            $requestData = [
                'power' => $power,
                'token' => $token,
                'time'  => $time,
            ];

            $client = new Client();
            $authApi = env('AR_AUTH_API_DOMAIN') . "common/validatetoken";
            $data = [
                'query' => $requestData
            ];

            // 请求数据
            $result = $client->request('GET', $authApi, $data);
            $content = $result->getBody()->getContents();
            $contentArr = json_decode($content, true);

            WriteLog::write("token", "详情：",
                "请求地址：" . $authApi . "，请求数据：" . wpt_json_encode($data) . "，返回数据：" . $content,
                'token/log');

            if (!$contentArr || !$contentArr['data']['auth']) {
                // token 校验失败
                return false;
            }

            // 数据权限
            $request->merge([
                'power_particle_arr' => $contentArr['data']['particles'],
            ]);

            // token 校验通过
            return true;

        } catch (\Exception $e) {

            $request->merge([
                'power_particle_arr' => [],
            ]);
            WriteLog::write("token", "token 校验异常:", $e->getMessage(), 'token');
            return false;
        }
    }
}