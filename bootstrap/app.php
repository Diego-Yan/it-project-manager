<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // [REVIEW-FIX] R5.1: 添加安全响应头中间件，防止点击劫持/MIME嗅探/XSS
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // [REVIEW-FIX] R5.1: 自定义异常处理 — 生产环境不暴露敏感信息
        $exceptions->dontReport([
            //
        ]);

        $exceptions->render(function (Throwable $e, $request) {
            if (app()->isProduction() && !($e instanceof \Illuminate\Auth\AuthenticationException)) {  // [REVIEW-FIX] SP9.1: 修正操作符优先级 — ! 绑定到 $e 导致 instanceof 永远 false
                // 404/403 保持原有渲染；500 等内部错误返回通用页面防信息泄露
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->view('errors.404', [], 404);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $status = $e->getStatusCode();
                    if (view()->exists("errors.{$status}")) {
                        return response()->view("errors.{$status}", [], $status);
                    }
                }
                return response()->view('errors.500', [], 500);
            }
            return null;
        });
    })->create();
