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
            session()->flash('error', '没有 Zabbix 管理权限');
            return;
        }
        $this->validate();
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
            session()->flash('error', '没有 Zabbix 管理权限');
            return;
        }
        $config = ZabbixConfig::findOrFail($id);
        $svc = new ZabbixService($config);
        $this->testConfigId = $id;
        $this->testResult = $svc->testConnection()
            ? "✓ {$config->name} 连接成功"
            : "✗ {$config->name} 连接失败，请检查 URL 和 Token";
    }

    // [FIX] #2: 编辑时不暴露真实 token，用占位符代替
    // 用户需要重新输入或留空保留原值
    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', '没有 Zabbix 管理权限');
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
            session()->flash('error', '没有删除权限');
            return;
        }
        ZabbixConfig::findOrFail($id)->delete();
    }

    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formUrl','formApiToken','formMinSeverity','formPollInterval']); $this->formMinSeverity=4; $this->formPollInterval=10; $this->formIsActive=true; }

    public function render()
    {
        $configs = ZabbixConfig::latest()->get();
        return view('livewire.itsm.zabbix', compact('configs'))
            ->layout('layouts.app', ['title' => 'Zabbix 集成']);
    }
}
