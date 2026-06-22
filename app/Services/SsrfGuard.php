<?php

namespace App\Services;

/**
 * [REVIEW-FIX-R5 #2 P2] SSRF 防护工具类。
 *
 * 问题：AiChat.callLlm()、ZabbixService、WebhookManager 保存的 URL 由管理员配置，
 * 服务器会向这些 URL 发起 HTTP 请求。恶意或被入侵的管理员可配置内网地址
 * (如 169.254.169.254 云元数据、192.168.x.x 内网服务) 进行 SSRF 探测/攻击。
 *
 * 防护策略：解析 URL 的 host → 解析为 IP → 校验 IP 是否属于内网/保留地址段。
 * 阻止的地址段：
 *   - 127.0.0.0/8        (loopback)
 *   - 10.0.0.0/8         (私有 A)
 *   - 172.16.0.0/12      (私有 B)
 *   - 192.168.0.0/16     (私有 C)
 *   - 169.254.0.0/16     (link-local, 含云元数据 169.254.169.254)
 *   - 0.0.0.0/8          (本网络)
 *   - ::1, fc00::/7      (IPv6 loopback/唯一本地)
 *   - fe80::/10          (IPv6 link-local)
 *
 * 用法:
 *   if (!SsrfGuard::isSafe($url)) { abort(403, '不允许的内网地址'); }
 */
class SsrfGuard
{
    /**
     * 校验 URL 是否指向非内网/保留地址。
     * 返回 true = 安全可请求；false = 应阻止。
     */
    public static function isSafe(string $url): bool
    {
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];

        // 解析 host 为 IP（可能是域名，需 DNS 解析）
        // gethostbynamel 返回 IP 数组或 false
        $ips = gethostbynamel($host);
        if ($ips === false) {
            // 无法解析 DNS — 可能是无效域名或内网短名，保守拒绝
            // 但允许 IP 直接作为 host 的情况
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return self::isIpSafe($host);
            }
            return false;
        }

        // 检查所有解析出的 IP
        foreach ($ips as $ip) {
            if (!self::isIpSafe($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 校验单个 IP 是否为安全的（非内网/保留地址）。
     */
    public static function isIpSafe(string $ip): bool
    {
        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // 阻止 ::1 (loopback) 和 fc00::/7 (唯一本地)、fe80::/10 (link-local)
            if ($ip === '::1') return false;
            if (str_starts_with($ip, 'fc') || str_starts_with($ip, 'fd')) return false;
            if (str_starts_with($ip, 'fe8') || str_starts_with($ip, 'fe9')
                || str_starts_with($ip, 'fea') || str_starts_with($ip, 'feb')) return false;
            return true;
        }

        return false;
    }
}
