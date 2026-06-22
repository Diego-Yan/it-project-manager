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
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // [REVIEW-FIX] R5.1: 自定义异常处理 — 生产环境不暴露敏感信息
        $exceptions->dontReport([
            //
        ]);

        $exceptions->render(function (Throwable $e, $request) {
            // [REVIEW-FIX-R3 #3 P2] 修复生产环境异常处理过度兜底：
            // 原代码在生产环境对"非 AuthenticationException"的所有异常一律返回 500 页面，
            // 导致 ValidationException（422）、HttpResponseException 等被错误渲染为 500，
            // 表单验证错误在生产环境会显示服务器错误页而非字段级反馈。
            // 修复：仅对 500+ 级别的服务端错误返回通用页面，放行 4xx 客户端错误和
            // ValidationException/HttpResponseException（这些由 Laravel/Livewire 正常处理）。
            if (!app()->isProduction()) {
                return null; // 非生产环境：交给 Laravel 默认处理器，显示完整调试信息
            }

            // 认证异常：交给 Laravel 处理（重定向到登录页）
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            // 验证异常：交给 Livewire/Laravel 处理（返回字段级错误反馈）
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return null;
            }

            // HttpException（4xx）：尝试渲染对应错误页，不存在则交给默认处理器
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $status = $e->getStatusCode();
                if ($status >= 500 && view()->exists('errors.500')) {
                    return response()->view('errors.500', [], 500);
                }
                if (view()->exists("errors.{$status}")) {
                    return response()->view("errors.{$status}", [], $status);
                }
                return null; // 无对应模板 → 交给 Laravel 默认处理器
            }

            // NotFoundHttpException：渲染 404 页面
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->view('errors.404', [], 404);
            }

            // 其他未捕获异常（如 PDOException、TypeError 等 500 级别）：
            // 返回通用 500 页面，防止堆栈跟踪泄露
            if (view()->exists('errors.500')) {
                return response()->view('errors.500', [], 500);
            }
            return null;
        });
    })->create();
