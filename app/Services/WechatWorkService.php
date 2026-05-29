<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WechatWorkService
{
    private string $corpId;
    private string $corpSecret;

    public function __construct()
    {
        $this->corpId = config('services.wechat.corp_id', '');
        $this->corpSecret = config('services.wechat.corp_secret', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->corpId) && !empty($this->corpSecret);
    }

    /**
     * 获取 access_token（缓存 2 小时）
     */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) return null;

        return Cache::remember('wechat_access_token', 7200 - 300, function () {
            $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'corpid' => $this->corpId,
                'corpsecret' => $this->corpSecret,
            ]);

            if ($resp->failed()) {
                Log::error('WeChat token failed', ['resp' => $resp->body()]);
                return null;
            }

            $data = $resp->json();
            return $data['access_token'] ?? null;
        });
    }

    /**
     * 同步部门成员列表
     * @return array<int, array{userid:string, name:string, department:string, mobile:string, email:string, position:string}>
     */
    public function listUsers(int $departmentId = 1, bool $fetchChild = true): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/user/simplelist', [
            'access_token' => $token,
            'department_id' => $departmentId,
            'fetch_child' => $fetchChild ? 1 : 0,
        ]);

        if ($resp->failed()) {
            Log::error('WeChat user list failed', ['resp' => $resp->body()]);
            return [];
        }

        $data = $resp->json();
        return $data['userlist'] ?? [];
    }

    /**
     * 获取单个用户详情
     */
    public function getUserInfo(string $userid): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/user/get', [
            'access_token' => $token,
            'userid' => $userid,
        ]);

        if ($resp->failed()) return null;
        return $resp->json();
    }
}
