<?php

namespace App\Livewire\Admin;

use App\Services\DingTalkService;
use App\Services\WechatWorkService;
use Livewire\Component;

class BotSettings extends Component
{
    public string $wechatCorpId = '';
    public string $wechatCorpSecret = '';
    public string $dingtalkAppKey = '';
    public string $dingtalkAppSecret = '';
    public string $testResult = '';

    public function mount(): void
    {
        $this->wechatCorpId = (string) config('services.wechat.corp_id', '');
        $this->wechatCorpSecret = (string) config('services.wechat.corp_secret', '');
        $this->dingtalkAppKey = (string) config('services.dingtalk.app_key', '');
        $this->dingtalkAppSecret = (string) config('services.dingtalk.app_secret', '');
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

        return view('livewire.admin.bot-settings', compact('wechatUrl', 'dingtalkUrl'))
            ->layout('layouts.app', ['title' => 'Bot 配置']);
    }
}
