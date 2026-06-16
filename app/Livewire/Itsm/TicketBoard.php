<?php

namespace App\Livewire\Itsm;

use App\Models\Asset;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Region;
use App\Models\Sla;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\NotificationService;
use Livewire\Component;
use Livewire\WithPagination;

class TicketBoard extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formTitle = '', $formDescription = '', $formType = 'request', $formPriority = 'medium', $formSource = 'portal';
    public int|string $formProjectId = '', $formRegionId = '', $formCategoryId = '', $formAssetId = '', $formAssignedTo = '';
    public bool $formIsProxy = false;     // 是否代填
    public int|string $formReportedFor = ''; // 代填给谁
    public string $newComment = ''; public ?int $viewTicketId = null;
    public int|string $assignToUserId = ''; // IT 主管分配工单给指定人员
    public array $suggestedEngineers = [];

    protected $rules = [
        'formTitle'       => 'required|max:200',
        'formRegionId'    => 'required|exists:regions,id',
        'formCategoryId'  => 'required|exists:project_categories,id',
        'formType'        => 'required|in:request,incident,change,problem',
        // [REVIEW-FIX] I3: 验证外键引用存在，防止存储孤立引用
        'formProjectId'   => 'nullable|exists:projects,id',
        'formAssetId'     => 'nullable|exists:assets,id',
        'formAssignedTo'  => 'nullable|exists:users,id',
        // [REVIEW-FIX] I3: 补全缺失的字段验证
        'formPriority'    => 'required|in:low,medium,high,urgent',
        'formDescription' => 'nullable|string|max:5000',
        'formReportedFor' => 'nullable|exists:users,id',
    ];

    public function save(): void
    {
        $this->validate();
        $data = [
            'title'=>$this->formTitle,'description'=>$this->formDescription?:null,
            'type'=>$this->formType,'priority'=>$this->formPriority,'source'=>$this->formSource,
            'project_id'=>$this->formProjectId?:null,'asset_id'=>$this->formAssetId?:null,
            'region_id'=>$this->formRegionId?:null, 'category_id'=>$this->formCategoryId?:null,
            'assigned_to'=>$this->formAssignedTo?:null,'created_by'=>auth()->id(),
            'reported_for'=>$this->formIsProxy ? ($this->formReportedFor ?: null) : null,
            'user_confirmed_at'=>$this->formIsProxy ? null : now(),
            'sla_deadline'=>Sla::getDeadline($this->formPriority),
        ];
        if ($this->editingId) {
            // [REVIEW-FIX] R12.1: 编辑工单需检查所有权或管理权限
            $ticket = Ticket::findOrFail($this->editingId);
            if ($ticket->created_by != auth()->id() && !auth()->user()->can('manage tickets')) {
                session()->flash('error', '只能编辑自己创建的工单');
                return;
            }
            $ticket->update($data);
        }
        else {
            $ticket = Ticket::create($data);
            // 代填工单 → 通知被代填人
            if ($ticket->reported_for && $ticket->reported_for != auth()->id()) {
                $creatorName = auth()->user()->name;
                $reportedForUser = User::find($ticket->reported_for);
                \App\Models\Notification::send($ticket->reported_for,
                    "{$creatorName} 代你提交了工单",
                    "工单: {$ticket->title}", 'info');
                try {
                    NotificationService::send('ticket.proxy_created', [
                        'project_id'    => $ticket->project_id,
                        'project_title' => '工单系统',
                        'task_title'    => $ticket->title,
                        'user_name'     => $creatorName,
                        'assignee_name' => $reportedForUser?->name,
                        'message'       => "{$ticket->creator->name} 代你提交了工单，请在系统中确认",
                    ]);
                } catch (\Throwable $e) {}
            }
        }
        $this->resetForm();
    }

    // 自己接单（任何 IT 工程师都可以）
    public function assign(int $id): void
    {
        // [REVIEW-FIX] C3: 原子性抢单 — 用 WHERE 条件防止竞态
        $updated = Ticket::where('id', $id)
            ->where('status', 'open')
            ->update(['assigned_to' => auth()->id(), 'status' => 'in_progress']);

        if ($updated) {
            \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
        }
    }

    // IT 主管分配工单给指定人员
    public function assignTo(int $id): void
    {
        if (!auth()->user()->can('manage tickets')) return;
        if (empty($this->assignToUserId)) return;

        Ticket::where('id', $id)->update(['assigned_to' => $this->assignToUserId, 'status' => 'in_progress']);
        // [REVIEW-FIX] I1: 刷新分配人和接收人双方侧边栏
        \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
        \App\View\Composers\SidebarComposer::flushForUser((int) $this->assignToUserId);
        $this->assignToUserId = '';
    }

    // 转让工单给其他 IT 成员
    public function transfer(int $id): void
    {
        if (empty($this->assignToUserId)) return;
        $ticket = Ticket::findOrFail($id);
        // 只有当前处理人或 IT 主管可以转让
        if ($ticket->assigned_to != auth()->id() && !auth()->user()->can('manage tickets')) return;
        $fromUser = $ticket->assignee?->name ?? '未分配';
        $toUser = User::find($this->assignToUserId)?->name ?? '未知';
        $ticket->update(['assigned_to' => $this->assignToUserId]);
        TicketComment::create(['ticket_id'=>$id, 'user_id'=>auth()->id(), 'content'=>"转让工单: {$fromUser} → {$toUser}"]);
        // [REVIEW-FIX] I1: 刷新转让方和接收方双方侧边栏
        \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
        \App\View\Composers\SidebarComposer::flushForUser((int) $this->assignToUserId);
        $this->assignToUserId = '';
        session()->flash('ticket_msg', "工单已转让给 {$toUser}");
    }

    public function resolve(int $id): void
    {
        // [REVIEW-FIX] R12.1: 解决工单需要管理权限
        if (!auth()->user()->can('manage tickets')) {
            session()->flash('error', '没有工单管理权限');
            return;
        }
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'in_progress') return;
        // [REVIEW-FIX] I14: 管理员可强制解决任何进行中工单，不仅限自己的
        if ($ticket->assigned_to != auth()->id() && !auth()->user()->can('manage tickets')) return;
        $ticket->update(['status'=>'resolved','resolved_by'=>auth()->id(),'resolved_at'=>now()]);
        \App\View\Composers\SidebarComposer::flushForUser(auth()->id()); // [REVIEW-FIX] P0.1
        TicketComment::create(['ticket_id'=>$id, 'user_id'=>auth()->id(), 'content'=>'标记为已解决']);
    }

    public string $closeNote = '';
    public bool $showCloseConfirm = false;
    public ?int $closingTicketId = null;

    public function confirmClose(int $id): void
    {
        $this->closingTicketId = $id;
        $this->closeNote = '';
        $this->showCloseConfirm = true;
    }

    public function close(): void // [REVIEW-FIX] M3: 空值防御
    {
        if (!auth()->user()->can('manage tickets')) {
            session()->flash('error', '没有工单管理权限');
            return;
        }
        if (!$this->closingTicketId) return;
        $ticket = Ticket::findOrFail($this->closingTicketId);
        if ($ticket->status !== 'resolved') return;

        if (empty(trim($this->closeNote))) {
            session()->flash('ticket_error', '请填写处理过程总结再关闭工单');
            return;
        }

        TicketComment::create(['ticket_id'=>$this->closingTicketId, 'user_id'=>auth()->id(), 'content'=>'关闭工单: '.trim($this->closeNote)]);
        $ticket->update(['status'=>'closed','closed_at'=>now()]);
        \App\View\Composers\SidebarComposer::flushForUser(auth()->id()); // [REVIEW-FIX] P0.1
        $this->showCloseConfirm = false;
        $this->closingTicketId = null;
        $this->closeNote = '';
        session()->flash('ticket_msg', '工单已关闭');
    }

    public function addComment(int $id): void
    {
        if (empty(trim($this->newComment))) return;
        TicketComment::create(['ticket_id'=>$id,'user_id'=>auth()->id(),'content'=>trim($this->newComment)]);
        $this->newComment = '';
    }

    public function edit(int $id): void
    {
        $t = Ticket::findOrFail($id);
        $this->editingId=$id; $this->formTitle=$t->title; $this->formDescription=$t->description??'';
        $this->formType=$t->type; $this->formPriority=$t->priority; $this->formSource=$t->source;
        $this->formProjectId=$t->project_id??''; $this->formRegionId=$t->region_id??''; $this->formCategoryId=$t->category_id??''; $this->formAssetId=$t->asset_id??''; $this->formAssignedTo=$t->assigned_to??'';
        $this->formIsProxy = (bool) $t->reported_for; $this->formReportedFor = $t->reported_for ?? '';
        $this->showForm=true; $this->updatedFormCategoryId(); // [REVIEW-FIX] C2: 方法名修正
    }

    // 系统分类联动：推荐负责该系统的 IT 工程师
    public function updatedFormCategoryId(): void
    {
        if (empty($this->formCategoryId)) { $this->suggestedEngineers = []; return; }
        $this->suggestedEngineers = User::whereHas('expertiseCategories', fn($q) => $q->where('category_id', $this->formCategoryId))
            ->where('is_active', true)->get(['id', 'name'])->toArray();
    }

    public function toggleView(int $id): void { $this->viewTicketId = $this->viewTicketId === $id ? null : $id; }
    public function delete(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->created_by != auth()->id() && !auth()->user()->can('manage tickets')) return;
        $ticket->delete();
        // [REVIEW-FIX] I10: 删除工单后刷新侧边栏计数
        \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
    }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formDescription','formType','formPriority','formSource','formProjectId','formRegionId','formCategoryId','formAssetId','formAssignedTo','formIsProxy','formReportedFor']); $this->formType='request'; $this->formPriority='medium'; $this->formSource='portal'; $this->suggestedEngineers=[]; $this->formIsProxy=false; $this->formReportedFor=''; }

    public function render()
    {
        $tickets = Ticket::with(['project','asset','assignee','creator','region','category'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $regions = Region::orderBy('sort_order')->get();
        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();
        $assets = Asset::orderBy('name')->get(['id','name','asset_tag']);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        $viewTicket = $this->viewTicketId ? Ticket::with('comments.user')->find($this->viewTicketId) : null;
        $openCount = Ticket::whereIn('status',['open','in_progress'])->count();
        return view('livewire.itsm.tickets', compact('tickets','projects','assets','users','regions','categories','viewTicket','openCount'))
            ->layout('layouts.app', ['title' => '工单管理']);
    }
}
