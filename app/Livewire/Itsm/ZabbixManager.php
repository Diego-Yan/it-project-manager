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

    protected $rules = [
        'formName' => 'required|max:100',
        'formUrl' => 'required|url|max:500',
        'formApiToken' => 'required|string|max:255',
    ];

    public function save(): void
    {
        $this->validate();
        $data = [
            'name' => $this->formName, 'url' => $this->formUrl, 'api_token' => $this->formApiToken,
            'min_severity' => $this->formMinSeverity, 'poll_interval' => $this->formPollInterval,
            'is_active' => $this->formIsActive,
        ];
        if ($this->editingId) { ZabbixConfig::findOrFail($this->editingId)->update($data); }
        else { ZabbixConfig::create($data); }
        $this->resetForm();
    }

    public function test(int $id): void
    {
        $config = ZabbixConfig::findOrFail($id);
        $svc = new ZabbixService($config);
        $this->testConfigId = $id;
        $this->testResult = $svc->testConnection()
            ? "✓ {$config->name} 连接成功"
            : "✗ {$config->name} 连接失败，请检查 URL 和 Token";
    }

    public function edit(int $id): void
    {
        $z = ZabbixConfig::findOrFail($id);
        $this->editingId=$id; $this->formName=$z->name; $this->formUrl=$z->url;
        $this->formApiToken=$z->api_token; $this->formMinSeverity=$z->min_severity;
        $this->formPollInterval=$z->poll_interval; $this->formIsActive=$z->is_active;
        $this->showForm=true;
    }

    public function delete(int $id): void { if (auth()->user()->can('manage incidents')) ZabbixConfig::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formUrl','formApiToken','formMinSeverity','formPollInterval']); $this->formMinSeverity=4; $this->formPollInterval=10; $this->formIsActive=true; }

    public function render()
    {
        $configs = ZabbixConfig::latest()->get();
        return view('livewire.itsm.zabbix', compact('configs'))
            ->layout('layouts.app', ['title' => 'Zabbix 集成']);
    }
}
