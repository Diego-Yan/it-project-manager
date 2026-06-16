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

    /** 获取 access_token（缓存 2 小时） */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) return null;

        // [REVIEW-FIX] R15.2: 缓存键添加命名空间前缀，防多实例/多租户冲突
        $cacheKey = 'itsm:dingtalk_token:' . sha1($this->appKey . ':' . $this->appSecret);
        return Cache::remember($cacheKey, 7100, function () {
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

    /** 获取子部门 ID 列表 */
    public function getSubDepartments(int $parentId = 1): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        // [REVIEW-FIX] C7: 旧版 oapi 端点需要 ?access_token= 查询参数，非 Bearer
        $resp = Http::post('https://oapi.dingtalk.com/topapi/v2/department/listsub', [
            'access_token' => $token,
            'dept_id' => $parentId,
        ]);

        if ($resp->failed()) return [];

        return $resp->json()['result'] ?? [];
    }

    /** 获取部门用户详情（分页，最多 100 条/页） */
    public function listUsers(int $parentDeptId = 1): array
    {
        $token = $this->getAccessToken();
        if (!$token) return [];

        $allUsers = [];
        $cursor = 0;

        do {
            // [REVIEW-FIX] C7: 旧版 oapi 端点需要 ?access_token= 查询参数
            $resp = Http::post('https://oapi.dingtalk.com/topapi/v2/user/list', [
                'access_token' => $token,
                'dept_id' => $parentDeptId,
                'cursor' => $cursor,
                'size' => 100,
            ]);

            if ($resp->failed()) break;

            $data = $resp->json();
            $list = $data['result']['list'] ?? [];

            foreach ($list as $user) {
                $allUsers[] = [
                    'userid' => $user['userid'] ?? '',
                    'name' => $user['name'] ?? '',
                ];
            }

            $cursor = $data['result']['next_cursor'] ?? 0;
        } while ($cursor > 0 && count($allUsers) < 5000);

        return $allUsers;
    }

    /** 获取全量用户（遍历所有部门） */
    public function listAllUsers(): array
    {
        $deptIds = [1]; // start from root
        $departments = $this->getSubDepartments(1);

        foreach ($departments as $dept) {
            $deptIds[] = $dept['dept_id'] ?? 0;
            // also get sub-sub-departments
            $subs = $this->getSubDepartments($dept['dept_id'] ?? 0);
            foreach ($subs as $sub) {
                $deptIds[] = $sub['dept_id'] ?? 0;
            }
        }

        $allUsers = [];
        $seen = [];

        foreach (array_unique($deptIds) as $deptId) {
            if ($deptId <= 0) continue;
            $users = $this->listUsers($deptId);
            foreach ($users as $user) {
                $uid = $user['userid'];
                if (!empty($uid) && !isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $allUsers[] = $user;
                }
            }
        }

        return $allUsers;
    }

    /** 获取单个用户详情 */
    public function getUserInfo(string $userid): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        // [REVIEW-FIX] C7: 旧版 oapi 端点需要 ?access_token= 查询参数
        $resp = Http::post('https://oapi.dingtalk.com/topapi/v2/user/get', [
            'access_token' => $token,
            'userid' => $userid,
        ]);

        if ($resp->failed()) return null;
        return $resp->json()['result'] ?? null;
    }
}
