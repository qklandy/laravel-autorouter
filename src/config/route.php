<?php
/**
 * 配置自动注入路由的中间件等
 */

return [
    "middleware" => [
        "controllers" => [
            "h/comectl" => ["token", "validate"],
        ],
        "actions"     => [
            "h/comectl/action"     => ["validate"],
        ]
    ]
];