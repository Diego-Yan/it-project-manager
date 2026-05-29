<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DingTalkService
{
    private string $appKey;
    private string $appSecret;

    public function __construct()
    {
        $this->appKey = config('services.dingtalk.app_key', '');
        $this->appSecret = config('services.dingtalk.app_secret', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appKey) && !empty($this->appSecret);
    }

    /**
     * 获取 access_token（缓存 2 小时）
     */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) return null;

        return Cache::remember('dingtalk_access_token', 7200 - 300, function () {
            $resp = Http::post('https://api.dingtalk.com/v1.0/oauth2/accessToken', [
                'appKey' => $this->appKey,
                'appSecret' => $this->appSecret,
            ]);

            if ($resp->failed()) {
                Log::error('DingTalk token failed', ['resp' => $resp->body()]);
                return null;
            }

            return $resp->json()['accessToken'] ?? null;
        });
    }

    /**
     * 获取部门用户列表（简化版，钉钉 API 较复杂）
     * @return array<int, array{userid:string, name:string, mobile:string, email:string, title:string}>
     */
    public function listUsers(int $cursor = 0, int $size = 100): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $resp = Http::withToken($token)
            ->post('https://api.dingtalk.com/v1.0/contact/users/lists', [
                'cursor' => $cursor,
                'size' => $size,
            ]);

        if ($resp->failed()) {
            Log::error('DingTalk user list failed', ['resp' => $resp->body()]);
            return [];
        }

        $data = $resp->json();
        $users = $data['list'] ?? [];

        // 分页递归
        if (!empty($data['nextCursor']) && count($users) >= $size) {
            $users = array_merge($users, $this->listUsers($data['nextCursor'], $size));
        }

        return $users;
    }

    /**
     * 获取单个用户详情
     */
    public function getUserInfo(string $userid): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $resp = Http::withToken($token)
            ->get("https://api.dingtalk.com/v1.0/contact/users/{$userid}");

        if ($resp->failed()) return null;
        return $resp->json();
    }
}
