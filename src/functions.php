<?php

if (!function_exists('qklin_json_encode')) {
    /**
     * json编码
     * @param $data
     * @return false|string
     */
    function qklin_json_encode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    }
}