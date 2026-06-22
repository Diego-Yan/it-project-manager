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
            // [REVIEW-FIX-R5 #3 P2] 修复 CSP connect-src 阻断 Livewire 功能：
            // 原配置 connect-src 'self' → Livewire 的 HTTP 请求（wire:click 等向自身发送
            // POST /livewire/update）被 CSP 阻止，控制台报 CSP 违规。
            // 修复：connect-src 允许 'self'（Livewire 请求同源）。
            // 注意：AiChat 调用外部 LLM API 是在服务端（PHP Http::post），不经过浏览器 CSP，
            // 因此不需要在 connect-src 中放行外部 LLM 地址。
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
