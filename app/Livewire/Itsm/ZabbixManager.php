<?php

namespace App\Livewire\Itsm;

use App\Models\ZabbixConfig;
use App\Services\ZabbixService;
use Livewire\Component;

class ZabbixManager extends Component
{
    public bool $showForm = false; public ?int $editingId = null;
    public string $formName = '', $formUrl = '', $formApiToken = '';
    public int $formMinSeverity = 4, $formPollInterval = 10;
    public bool $formIsActive = true;
    public string $testResult = '';
    public ?int $testConfigId = null;

    protected function rules(): array
    {
        // [REVIEW-FIX] R16.3: 编辑时 token 留空=保留原值，不再 require
        $tokenRule = $this->editingId ? 'nullable|string|max:255' : 'required|string|max:255';
        return [
            'formName' => 'required|max:100',
            'formUrl' => 'required|url|max:500',
            'formApiToken' => $tokenRule,
        ];
    }

    public function save(): void
    {
        // [REVIEW-FIX] R12.3: Zabbix 配置管理需权限检查
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有 Zabbix 管理权限'));
            return;
        }
        $this->validate();

        // [REVIEW-FIX-R5 #2 P2] SSRF 防护：Zabbix 服务器地址需校验，但允许内网地址
        // （Zabbix 通常部署在内网，与 AiChat/Webhook 不同，这里仅阻止云元数据等高危地址）。
        // 注意：Zabbix 内网场景允许，用 SsrfGuard::isIpSafe 逐个检查而非全量阻止。
        // 对于 Zabbix，管理员应能配置内网地址，所以此处仅阻止 169.254.x.x 等保留段。
        $parsed = parse_url($this->formUrl);
        if (!empty($parsed['host'])) {
            $ips = gethostbynamel($parsed['host']);
            if ($ips === false && filter_var($parsed['host'], FILTER_VALIDATE_IP)) {
                $ips = [$parsed['host']];
            }
            if ($ips !== false) {
                foreach ($ips as $ip) {
                    // 仅阻止 link-local（含云元数据 169.254.169.254）和 0.0.0.0
                    if (str_starts_with($ip, '169.254.') || $ip === '0.0.0.0') {
                        $this->addError('formUrl', __('不允许的 Zabbix 地址：不能指向 link-local 或保留地址段。'));
                        return;
                    }
                }
            }
        }

        $data = [
            'name' => $this->formName, 'url' => $this->formUrl,
            'min_severity' => $this->formMinSeverity, 'poll_interval' => $this->formPollInterval,
            'is_active' => $this->formIsActive,
        ];
        // 编辑时留空 token 表示保留原值，不更新
        if (!$this->editingId || !empty($this->formApiToken)) {
            $data['api_token'] = $this->formApiToken;
        }
        if ($this->editingId) { ZabbixConfig::findOrFail($this->editingId)->update($data); }
        else { ZabbixConfig::create($data); }
        $this->resetForm();
    }

    public function test(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有 Zabbix 管理权限'));
            return;
        }
        $config = ZabbixConfig::findOrFail($id);
        $svc = new ZabbixService($config);
        $this->testConfigId = $id;
        $this->testResult = $svc->testConnection()
            ? __("✓ :name 连接成功", ['name' => $config->name])
            : __("✗ :name 连接失败，请检查 URL 和 Token", ['name' => $config->name]);
    }

    // [FIX] #2: 编辑时不暴露真实 token，用占位符代替
    // 用户需要重新输入或留空保留原值
    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有 Zabbix 管理权限'));
            return;
        }
        $z = ZabbixConfig::findOrFail($id);
        $this->editingId=$id; $this->formName=$z->name; $this->formUrl=$z->url;
        // [FIX] #2: 不再填充真实 token，防止前端泄露
        $this->formApiToken='';  // 留空表示保留原值
        $this->formMinSeverity=$z->min_severity;
        $this->formPollInterval=$z->poll_interval; $this->formIsActive=$z->is_active;
        $this->showForm=true;
    }

    // [FIX] #4: 添加权限检查
    public function delete(int $id): void {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有删除权限'));
            return;
        }
        ZabbixConfig::findOrFail($id)->delete();
    }

    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formUrl','formApiToken','formMinSeverity','formPollInterval']); $this->formMinSeverity=4; $this->formPollInterval=10; $this->formIsActive=true; }

    public function render()
    {
        $configs = ZabbixConfig::latest()->get();
        return view('livewire.itsm.zabbix', compact('configs'))
            ->layout('layouts.app', ['title' => __('Zabbix 集成')]);
    }
}
