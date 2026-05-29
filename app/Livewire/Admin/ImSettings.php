<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\DingTalkService;
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
        $this->wechatCorpSecret = (string) config('services.wechat.corp_secret', '');
        $this->dingtalkAppKey = (string) config('services.dingtalk.app_key', '');
        $this->dingtalkAppSecret = (string) config('services.dingtalk.app_secret', '');
        $this->wechatUserCount = User::where('source', 'wechat')->count();
        $this->dingtalkUserCount = User::where('source', 'dingtalk')->count();
    }

    // ── 企微通讯录同步 ──────────────────────────────────

    public function syncWechatUsers(): void
    {
        $svc = new WechatWorkService;
        if (!$svc->isConfigured()) {
            $this->syncResult = '请先保存企业微信 API 凭证';
            return;
        }

        $users = $svc->listAllUsers();
        if (empty($users)) {
            $this->syncResult = '未获取到企业微信用户，请检查 API 凭证和应用可见范围';
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
                $user->assignRole('普通成员');
                $created++;
            }
        }

        $this->wechatUserCount = User::where('source', 'wechat')->count();
        $this->syncResult = "✓ 企业微信同步完成：新建 {$created}，更新 {$updated}";
    }

    // ── 钉钉通讯录同步 ──────────────────────────────────

    public function syncDingtalkUsers(): void
    {
        $svc = new DingTalkService;
        if (!$svc->isConfigured()) {
            $this->syncResult = '请先保存钉钉 API 凭证';
            return;
        }

        $users = $svc->listAllUsers();
        if (empty($users)) {
            $this->syncResult = '未获取到钉钉用户，请检查 API 凭证和应用权限范围';
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
                $user->assignRole('普通成员');
                $created++;
            }
        }

        $this->dingtalkUserCount = User::where('source', 'dingtalk')->count();
        $this->syncResult = "✓ 钉钉同步完成：新建 {$created}，更新 {$updated}";
    }

    public function saveWechat(): void
    {
        $this->updateEnv([
            'WECHAT_CORP_ID' => $this->wechatCorpId,
            'WECHAT_CORP_SECRET' => $this->wechatCorpSecret,
        ]);
        session()->flash('success', '企业微信配置已保存');
    }

    public function saveDingtalk(): void
    {
        $this->updateEnv([
            'DINGTALK_APP_KEY' => $this->dingtalkAppKey,
            'DINGTALK_APP_SECRET' => $this->dingtalkAppSecret,
        ]);
        session()->flash('success', '钉钉配置已保存');
    }

    public function testWechat(): void
    {
        $svc = new WechatWorkService;
        if (!$svc->isConfigured()) {
            $this->testResult = '请先保存 Corp ID 和 Secret';
            return;
        }
        $token = $svc->getAccessToken();
        $this->testResult = $token ? '✓ 企业微信连接成功' : '✗ 连接失败，请检查 Corp ID 和 Secret';
    }

    public function testDingtalk(): void
    {
        $svc = new DingTalkService;
        if (!$svc->isConfigured()) {
            $this->testResult = '请先保存 App Key 和 Secret';
            return;
        }
        $token = $svc->getAccessToken();
        $this->testResult = $token ? '✓ 钉钉连接成功' : '✗ 连接失败，请检查 App Key 和 Secret';
    }

    private function updateEnv(array $updates): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $written = [];
        $newLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) { $newLines[] = $line; continue; }
            $eqPos = strpos($trimmed, '=');
            if ($eqPos === false) { $newLines[] = $line; continue; }
            $key = trim(substr($trimmed, 0, $eqPos));
            if (array_key_exists($key, $updates)) {
                if (!isset($written[$key])) {
                    $newLines[] = $key . '=' . $updates[$key];
                    $written[$key] = true;
                }
            } else {
                $newLines[] = $line;
            }
        }
        foreach ($updates as $key => $value) {
            if (!isset($written[$key])) { $newLines[] = $key . '=' . $value; }
        }
        file_put_contents($envPath, implode("\n", $newLines) . "\n");
        \Illuminate\Support\Facades\Artisan::call('config:clear');
    }

    public function render()
    {
        $wechatUrl = url('/api/bot/wechat');
        $dingtalkUrl = url('/api/bot/dingtalk');

        return view('livewire.admin.im-settings', compact('wechatUrl', 'dingtalkUrl'))
            ->layout('layouts.app', ['title' => 'IM 接入']);
    }
}
