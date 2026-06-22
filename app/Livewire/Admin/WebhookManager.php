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
    // [REVIEW-FIX-R1 #8 P1] 修复 fatal error：原属性声明使用 __() 函数调用作为默认值，
    // 违反 PHP 常量表达式规则。改为声明空数组 + boot() 中初始化。
    public array $availableEvents = [];

    protected $rules = [
        'formName'    => 'required|string|max:100',
        'formUrl'     => 'required|url|max:500',
        'formType'    => 'required|in:wechat,dingtalk,custom',
    ];

    // [REVIEW-FIX-R1 #8 P1] boot() 在每次 Livewire 请求执行，初始化含翻译的事件列表
    public function boot(): void
    {
        $this->availableEvents = [
            'project.created'       => __('项目创建'),
            'project.completed'     => __('项目完成'),
            'project.deadline_near' => __('项目即将到期'),
            'project.overdue'       => __('项目逾期'),
            'task.assigned'         => __('任务分配'),
            'task.confirmed'        => __('任务确认'),
            'task.completed'        => __('任务完成'),
            'task.rejected'         => __('任务被拒绝'),
            'task.unassigned'       => __('任务待认领'),
            'task.deadline_near'    => __('任务即将到期'),
            'member.joined'         => __('新成员加入'),
            'application.submitted' => __('新的加入申请'),
            'ticket.proxy_created'  => __('代填工单创建'),
            'daily.digest'          => __('每日概报'),
        ];
    }

    public function save(): void
    {
        $this->guard(); // [REVIEW-FIX] P1.5
        $this->validate();

        // [REVIEW-FIX-R5 #2 P2] SSRF 防护：阻止管理员配置内网/保留地址段 URL。
        // 服务器会向此 URL 发送 webhook，恶意/被入侵管理员可配置 169.254.169.254
        // (云元数据) 或 192.168.x.x (内网服务) 进行 SSRF 探测。
        if (!\App\Services\SsrfGuard::isSafe($this->formUrl)) {
            $this->addError('formUrl', __('不允许的 Webhook 地址：不能指向内网或保留地址段。'));
            return;
        }

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
            session()->flash('success', __('Webhook 已更新。'));
        } else {
            WebhookConfig::create($data);
            session()->flash('success', __('Webhook 已创建。'));
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
        session()->flash('success', __('Webhook 已删除。'));
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
            ->layout('layouts.app', ['title' => __('Webhook 管理')]);
    }
}
