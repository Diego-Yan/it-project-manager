<?php

namespace App\Livewire\Admin;

use App\Models\WebhookConfig;
use Livewire\Component;

class WebhookManager extends Component
{
    public ?int $editingId = null;
    public bool $showForm = false;

    public string $formName = '';
    public int|string $formProjectId = ''; // '' = 全局
    public string $formUrl = '';
    public string $formType = 'custom';
    public array $selectedEvents = [];
    public bool $formIsActive = true;

    // 可选事件列表
    public array $availableEvents = [
        'project.created'       => '项目创建',
        'project.completed'     => '项目完成',
        'project.deadline_near' => '项目即将到期',
        'project.overdue'       => '项目逾期',
        'task.assigned'         => '任务分配',
        'task.confirmed'        => '任务确认',
        'task.completed'        => '任务完成',
        'task.unassigned'       => '任务待认领',
        'member.joined'         => '新成员加入',
        'application.submitted' => '新的加入申请',
    ];

    protected $rules = [
        'formName'    => 'required|string|max:100',
        'formUrl'     => 'required|url|max:500',
        'formType'    => 'required|in:wechat,dingtalk,custom',
    ];

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'       => $this->formName,
            'url'        => $this->formUrl,
            'type'       => $this->formType,
            'events'     => !empty($this->selectedEvents) ? array_values($this->selectedEvents) : null,
            'is_active'  => $this->formIsActive,
            'project_id' => $this->formProjectId ?: null,
        ];

        if ($this->editingId) {
            WebhookConfig::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Webhook 已更新。');
        } else {
            WebhookConfig::create($data);
            session()->flash('success', 'Webhook 已创建。');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $webhook = WebhookConfig::findOrFail($id);
        $this->editingId       = $id;
        $this->formName        = $webhook->name;
        $this->formUrl         = $webhook->url;
        $this->formType        = $webhook->type;
        $this->formProjectId   = $webhook->project_id ?? '';
        $this->formIsActive    = $webhook->is_active;
        $this->selectedEvents  = $webhook->events ?? [];
        $this->showForm        = true;
    }

    public function delete(int $id): void
    {
        WebhookConfig::findOrFail($id)->delete();
        session()->flash('success', 'Webhook 已删除。');
    }

    public function toggleActive(int $id): void
    {
        $webhook = WebhookConfig::findOrFail($id);
        $webhook->update(['is_active' => !$webhook->is_active]);
    }

    public function resetForm(): void
    {
        $this->showForm   = false;
        $this->editingId  = null;
        $this->reset(['formName','formUrl','formType','formProjectId','selectedEvents','formIsActive']);
        $this->formType   = 'custom';
        $this->formIsActive = true;
    }

    public function render()
    {
        $webhooks = WebhookConfig::with('project')->latest()->get();
        $projects = \App\Models\Project::orderBy('title')->get(['id', 'title']);

        return view('livewire.admin.webhook-manager', compact('webhooks', 'projects'))
            ->layout('layouts.app', ['title' => 'Webhook 管理']);
    }
}
