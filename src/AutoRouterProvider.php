<?php

namespace Qklin\AutoRouter;

use Illuminate\Support\ServiceProvider;
use Qklin\AutoRouter\Services\Annotate;
use Qklin\AutoRouter\Services\AutoRouter;
use Qklin\AutoRouter\Services\MiddleWare;
use Qklin\AutoRouter\Services\Middleware\ApiThrottleService;
use Qklin\AutoRouter\Services\Middleware\TokenMiddlewareService;
use Qklin\AutoRouter\Services\Router;

class AutoRouterProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 注册路由辅助工具
        if (!$this->app->bound('autorouter.router')) {
            $this->app->singleton('autorouter.router', Router::class);
        }
        if (!$this->app->bound('autorouter.middleware')) {
            $this->app->singleton('autorouter.middleware', MiddleWare::class);
        }
        if (!$this->app->bound('autorouter.annotate')) {
            $this->app->singleton('autorouter.annotate', Annotate::class);
        }

        // 注册默认提供中间件服务
        if (!$this->app->bound('autorouter.middleware.apithrottle')) {
            $this->app->singleton('autorouter.middleware.apithrottle', ApiThrottleService::class);
        }
        if (!$this->app->bound('autorouter.middleware.token')) {
            $this->app->singleton('autorouter.middleware.token', TokenMiddlewareService::class);
        }

        // 注册自动路由入口
        if (!$this->app->bound('autorouter.enter')) {
            $this->app->singleton('autorouter.enter', AutoRouter::class);
        }
    }

    /**
     * 启动
     */
    public function boot()
    {
        // 默认启动 自动路由
        if (env('AUTOROUTER_PROVIDER_START', 1)) {
            $this->app->make('autorouter.enter')->handle();
        }
    }
}
