<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * [REVIEW-FIX] R5.1 + R5.2 + C1: 统一安全响应头
     * - Content-Security-Policy 防止 XSS（替代已弃用的 X-XSS-Protection）
     * - 防止点击劫持 (X-Frame-Options)
     * - 防止 MIME 类型嗅探 (X-Content-Type-Options)
     * - Referrer 策略控制
     * - 权限策略禁用敏感 API
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            // CSP: default-src 'self' 为基础，script-src 允许 unsafe-inline（Livewire/Alpine 需要）
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; base-uri 'self'; object-src 'none'"
            );
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

            // HSTS: 仅 HTTPS 环境生效（本地开发跳过）
            if ($request->isSecure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
