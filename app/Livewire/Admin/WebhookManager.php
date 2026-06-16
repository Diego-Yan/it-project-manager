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
    // [REVIEW-FIX] R17.4: 补全 NotificationService/SendWebhookNotification 中已实现的新事件类型
    public array $availableEvents = [
        'project.created'       => '项目创建',
        'project.completed'     => '项目完成',
        'project.deadline_near' => '项目即将到期',
        'project.overdue'       => '项目逾期',
        'task.assigned'         => '任务分配',
        'task.confirmed'        => '任务确认',
        'task.completed'        => '任务完成',
        'task.rejected'         => '任务被拒绝',
        'task.unassigned'       => '任务待认领',
        'task.deadline_near'    => '任务即将到期',
        'member.joined'         => '新成员加入',
        'application.submitted' => '新的加入申请',
        'ticket.proxy_created'  => '代填工单创建',
        'daily.digest'          => '每日概报',
    ];

    protected $rules = [
        'formName'    => 'required|string|max:100',
        'formUrl'     => 'required|url|max:500',
        'formType'    => 'required|in:wechat,dingtalk,custom',
    ];

    public function save(): void
    {
        $this->guard(); // [REVIEW-FIX] P1.5
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
        $this->guard(); // [REVIEW-FIX] P1.5
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
        $this->guard(); // [REVIEW-FIX] P1.5
        WebhookConfig::findOrFail($id)->delete();
        session()->flash('success', 'Webhook 已删除。');
    }

    public function toggleActive(int $id): void
    {
        $this->guard(); // [REVIEW-FIX] P1.5
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

    // [REVIEW-FIX] P1.5: Livewire action 绕过路由中间件，需内联权限检查
    private function guard(): void
    {
        if (!auth()->user()->can('manage roles')) abort(403);
    }

    public function render()
    {
        $webhooks = WebhookConfig::with('project')->latest()->get();
        $projects = \App\Models\Project::orderBy('title')->get(['id', 'title']);

        return view('livewire.admin.webhook-manager', compact('webhooks', 'projects'))
            ->layout('layouts.app', ['title' => 'Webhook 管理']);
    }
}
