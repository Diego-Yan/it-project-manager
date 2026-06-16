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

    /** 获取 access_token（缓存 2 小时） */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) return null;

        // [REVIEW-FIX] R15.2: 缓存键添加命名空间前缀，防多实例/多租户冲突
        $cacheKey = 'itsm:wechat_token:' . sha1($this->corpId . ':' . $this->corpSecret);
        return Cache::remember($cacheKey, 7100, function () {
            $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'corpid' => $this->corpId,
                'corpsecret' => $this->corpSecret,
            ]);

            if ($resp->failed() || ($resp->json()['errcode'] ?? -1) !== 0) {
                Log::error('WeChat token failed', ['resp' => $resp->body()]);
                return null;
            }

            return $resp->json()['access_token'] ?? null;
        });
    }

    /** 获取部门列表 */
    public function getDepartments(int $parentId = 0): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/department/list', [
            'access_token' => $token,
            'id' => $parentId,
        ]);

        if (($resp->json()['errcode'] ?? -1) !== 0) {
            Log::error('WeChat dept list failed', ['resp' => $resp->body()]);
            return [];
        }

        return $resp->json()['department'] ?? [];
    }

    /** 获取部门用户详情列表 */
    public function listUsers(int $departmentId = 1, bool $fetchChild = true): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/user/list', [
            'access_token' => $token,
            'department_id' => $departmentId,
            'fetch_child' => $fetchChild ? 1 : 0,
        ]);

        if (($resp->json()['errcode'] ?? -1) !== 0) {
            Log::error('WeChat user list failed', ['dept' => $departmentId, 'resp' => $resp->body()]);
            return [];
        }

        return $resp->json()['userlist'] ?? [];
    }

    /** 获取单个用户详情 */
    public function getUserInfo(string $userid): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $resp = Http::get('https://qyapi.weixin.qq.com/cgi-bin/user/get', [
            'access_token' => $token,
            'userid' => $userid,
        ]);

        if (($resp->json()['errcode'] ?? -1) !== 0) return null;
        return $resp->json();
    }

    /** 获取全量用户（先拿部门，再遍历拿用户） */
    public function listAllUsers(): array
    {
        $departments = $this->getDepartments();
        if (empty($departments)) {
            // fallback: try root department directly
            return $this->listUsers(1, true);
        }

        $allUsers = [];
        $seen = [];

        foreach ($departments as $dept) {
            $users = $this->listUsers($dept['id'], false);
            foreach ($users as $user) {
                $uid = $user['userid'];
                if (!isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $allUsers[] = $user;
                }
            }
        }

        return $allUsers;
    }
}
