<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\DingTalkService;
use App\Services\EnvService;
use App\Services\WechatWorkService;
use Livewire\Component;

class ImSettings extends Component
{
    public string $wechatCorpId = '';
    public string $wechatCorpSecret = '';
    public string $dingtalkAppKey = '';
    public string $dingtalkAppSecret = '';
    public string $testResult = '';
    public string $syncResult = '';
    public int $wechatUserCount = 0;
    public int $dingtalkUserCount = 0;

    public function mount(): void
    {
        $this->wechatCorpId = (string) config('services.wechat.corp_id', '');
        $this->dingtalkAppKey = (string) config('services.dingtalk.app_key', '');
        // [REVIEW-FIX] R4.2: 不在 mount 中加载 Secret，防止 Livewire 序列化泄露
        // 用户需重新输入或留空保留原值；测试/sync时从 config 临时读取
        $this->wechatUserCount = User::where('source', 'wechat')->count();
        $this->dingtalkUserCount = User::where('source', 'dingtalk')->count();
    }

    // ── 企微通讯录同步 ──────────────────────────────────

    public function syncWechatUsers(): void
    {
        // [REVIEW-FIX] I4: 同步操作需要权限检查（Livewire action 绕过路由中间件）
        if (!auth()->user()->can('manage roles')) {
            session()->flash('error', __('没有管理权限'));
            return;
        }

        // [REVIEW-FIX-R7 #2 P2] 通讯录同步限流：防止频繁调用企微/钉钉 API 导致接口限流或封禁。
        // 企微/钉钉 API 有调用频率限制，频繁同步可能导致 access_token 被限流甚至应用被封。
        // 限制：每管理员每小时最多 3 次同步操作。
        $rateKey = 'im_sync_rate:' . auth()->id();
        $syncCount = (int) \Illuminate\Support\Facades\Cache::get($rateKey, 0);
        if ($syncCount >= 3) {
            $this->syncResult = __('同步操作过于频繁，每小时最多 3 次，请稍后再试。');
            return;
        }

        $svc = new WechatWorkService;
        if (!$svc->isConfigured()) {
            $this->syncResult = __('请先保存企业微信 API 凭证');
            return;
        }

        $users = $svc->listAllUsers();
        if (empty($users)) {
            $this->syncResult = __('未获取到企业微信用户，请检查 API 凭证和应用可见范围');
            return;
        }

        $created = 0; $updated = 0;
        foreach ($users as $u) {
            $userid = $u['userid'] ?? '';
            $name = $u['name'] ?? $userid;
            if (empty($userid)) continue;

            $user = User::where('wechat_userid', $userid)->first();

            if ($user) {
                $user->update(['name' => $name, 'source' => 'wechat']);
                $updated++;
            } else {
                $user = User::create([
                    'name' => $name,
                    'username' => 'wx_' . $userid,
                    'wechat_userid' => $userid,
                    'source' => 'wechat',
                    'password' => bcrypt(str()->random(32)),
                    'is_active' => true,
                ]);
                $user->assignRole(__('普通员工'));
                $created++;
            }
        }

        $this->wechatUserCount = User::where('source', 'wechat')->count();
        $this->syncResult = __('✓ 企业微信同步完成：新建 :created，更新 :updated', ['created' => $created, 'updated' => $updated]);

        // [REVIEW-FIX-R7 #2 P2] 记录同步限流计数
        \Illuminate\Support\Facades\Cache::put($rateKey, $syncCount + 1, 3600);
    }

    // ── 钉钉通讯录同步 ──────────────────────────────────

    public function syncDingtalkUsers(): void
    {
        // [REVIEW-FIX] I4: 同步操作需要权限检查
        if (!auth()->user()->can('manage roles')) {
            session()->flash('error', __('没有管理权限'));
            return;
        }

        // [REVIEW-FIX-R7 #2 P2] 钉钉同步同样限流（与企微共享计数）
        $rateKey = 'im_sync_rate:' . auth()->id();
        $syncCount = (int) \Illuminate\Support\Facades\Cache::get($rateKey, 0);
        if ($syncCount >= 3) {
            $this->syncResult = __('同步操作过于频繁，每小时最多 3 次，请稍后再试。');
            return;
        }

        $svc = new DingTalkService;
        if (!$svc->isConfigured()) {
            $this->syncResult = __('请先保存钉钉 API 凭证');
            return;
        }

        $users = $svc->listAllUsers();
        if (empty($users)) {
            $this->syncResult = __('未获取到钉钉用户，请检查 API 凭证和应用权限范围');
            return;
        }

        $created = 0; $updated = 0;
        foreach ($users as $u) {
            $userid = $u['userid'] ?? '';
            $name = $u['name'] ?? $userid;
            if (empty($userid)) continue;

            $user = User::where('dingtalk_userid', $userid)->first();

            if ($user) {
                $user->update(['name' => $name, 'source' => 'dingtalk']);
                $updated++;
            } else {
                $user = User::create([
                    'name' => $name,
                    'username' => 'dt_' . $userid,
                    'dingtalk_userid' => $userid,
                    'source' => 'dingtalk',
                    'password' => bcrypt(str()->random(32)),
                    'is_active' => true,
                ]);
                $user->assignRole(__('普通员工'));
                $created++;
            }
        }

        $this->dingtalkUserCount = User::where('source', 'dingtalk')->count();
        $this->syncResult = __('✓ 钉钉同步完成：新建 :created，更新 :updated', ['created' => $created, 'updated' => $updated]);

        // [REVIEW-FIX-R7 #2 P2] 记录同步限流计数
        \Illuminate\Support\Facades\Cache::put($rateKey, $syncCount + 1, 3600);
    }

    public function saveWechat(): void
    {
        // [REVIEW-FIX] SP4.3: Livewire action 绕过路由中间件，需内联权限检查
        if (!auth()->user()->can('manage roles')) { session()->flash('error', __('没有管理权限')); return; }
        $secret = $this->wechatCorpSecret ?: config('services.wechat.corp_secret', '');
        $this->updateEnv([
            'WECHAT_CORP_ID' => $this->wechatCorpId,
            'WECHAT_CORP_SECRET' => $secret,
        ]);
        session()->flash('success', __('企业微信配置已保存'));
    }

    public function saveDingtalk(): void
    {
        // [REVIEW-FIX] SP4.4: Livewire action 绕过路由中间件，需内联权限检查
        if (!auth()->user()->can('manage roles')) { session()->flash('error', __('没有管理权限')); return; }
        $secret = $this->dingtalkAppSecret ?: config('services.dingtalk.app_secret', '');
        $this->updateEnv([
            'DINGTALK_APP_KEY' => $this->dingtalkAppKey,
            'DINGTALK_APP_SECRET' => $secret,
        ]);
        session()->flash('success', __('钉钉配置已保存'));
    }

    public function testWechat(): void
    {
        // [REVIEW-FIX] SP4.5: 权限检查 — 防止未授权连接探测
        if (!auth()->user()->can('manage roles')) { session()->flash('error', __('没有管理权限')); return; }
        $svc = new WechatWorkService;
        if (!$svc->isConfigured()) {
            $this->testResult = __('请先保存 Corp ID 和 Secret');
            return;
        }
        $token = $svc->getAccessToken();
        $this->testResult = $token ? __('✓ 企业微信连接成功') : __('✗ 连接失败，请检查 Corp ID 和 Secret');
    }

    public function testDingtalk(): void
    {
        // [REVIEW-FIX] SP4.6: 权限检查 — 防止未授权连接探测
        if (!auth()->user()->can('manage roles')) { session()->flash('error', __('没有管理权限')); return; }
        $svc = new DingTalkService;
        if (!$svc->isConfigured()) {
            $this->testResult = __('请先保存 App Key 和 Secret');
            return;
        }
        $token = $svc->getAccessToken();
        $this->testResult = $token ? __('✓ 钉钉连接成功') : __('✗ 连接失败，请检查 App Key 和 Secret');
    }

    // [REVIEW-FIX] C3: updateEnv() 已提取至 app/Services/EnvService.php
    private function updateEnv(array $updates): void
    {
        EnvService::write($updates);
    }

    /**
     * [REVIEW-FIX] R4.2: dehydrate 时清除敏感字段
     */
    public function dehydrate(): void
    {
        $this->wechatCorpSecret = '';
        $this->dingtalkAppSecret = '';
    }

    public function render()
    {
        $wechatUrl = url('/api/bot/wechat');
        $dingtalkUrl = url('/api/bot/dingtalk');

        return view('livewire.admin.im-settings', compact('wechatUrl', 'dingtalkUrl'))
            ->layout('layouts.app', ['title' => __('IM 接入')]);
    }
}
