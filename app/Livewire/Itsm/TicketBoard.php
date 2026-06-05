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
use Livewire\Component;
use Livewire\WithPagination;

class TicketBoard extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formTitle = '', $formDescription = '', $formType = 'request', $formPriority = 'medium', $formSource = 'portal';
    public int|string $formProjectId = '', $formRegionId = '', $formCategoryId = '', $formAssetId = '', $formAssignedTo = '';
    public string $newComment = ''; public ?int $viewTicketId = null;
    public int|string $assignToUserId = ''; // IT 主管分配工单给指定人员
    public array $suggestedEngineers = [];

    protected $rules = ['formTitle'=>'required|max:200', 'formRegionId'=>'required|exists:regions,id', 'formCategoryId'=>'required|exists:project_categories,id', 'formType'=>'required|in:request,incident,change,problem'];

    public function save(): void
    {
        $this->validate();
        $data = [
            'title'=>$this->formTitle,'description'=>$this->formDescription?:null,
            'type'=>$this->formType,'priority'=>$this->formPriority,'source'=>$this->formSource,
            'project_id'=>$this->formProjectId?:null,'asset_id'=>$this->formAssetId?:null,
            'region_id'=>$this->formRegionId?:null, 'category_id'=>$this->formCategoryId?:null,
            'assigned_to'=>$this->formAssignedTo?:null,'created_by'=>auth()->id(),
            'sla_deadline'=>Sla::getDeadline($this->formPriority),
        ];
        if ($this->editingId) { Ticket::findOrFail($this->editingId)->update($data); }
        else { Ticket::create($data); }
        $this->resetForm();
    }

    // 自己接单（任何 IT 工程师都可以）
    public function assign(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'open') return;
        $ticket->update(['assigned_to'=>auth()->id(),'status'=>'in_progress']);
    }

    // IT 主管分配工单给指定人员
    public function assignTo(int $id): void
    {
        if (!auth()->user()->can('manage tickets')) return;
        if (empty($this->assignToUserId)) return;

        $ticket = Ticket::findOrFail($id);
        $ticket->update(['assigned_to' => $this->assignToUserId, 'status' => 'in_progress']);
        $this->assignToUserId = '';
    }

    // 转让工单给其他 IT 成员
    public function transfer(int $id): void
    {
        if (empty($this->assignToUserId)) return;
        $ticket = Ticket::findOrFail($id);
        $fromUser = $ticket->assignee?->name ?? '未分配';
        $toUser = User::find($this->assignToUserId)?->name ?? '未知';
        $ticket->update(['assigned_to' => $this->assignToUserId]);
        TicketComment::create(['ticket_id'=>$id, 'user_id'=>auth()->id(), 'content'=>"转让工单: {$fromUser} → {$toUser}"]);
        $this->assignToUserId = '';
        session()->flash('ticket_msg', "工单已转让给 {$toUser}");
    }

    public function resolve(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'in_progress' || $ticket->assigned_to != auth()->id()) return;
        $ticket->update(['status'=>'resolved','resolved_by'=>auth()->id(),'resolved_at'=>now()]);
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

    public function close(): void
    {
        $ticket = Ticket::findOrFail($this->closingTicketId);
        if ($ticket->status !== 'resolved') return;

        if (empty(trim($this->closeNote))) {
            session()->flash('ticket_error', '请填写处理过程总结再关闭工单');
            return;
        }

        TicketComment::create(['ticket_id'=>$this->closingTicketId, 'user_id'=>auth()->id(), 'content'=>'关闭工单: '.trim($this->closeNote)]);
        $ticket->update(['status'=>'closed','closed_at'=>now()]);
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
        $this->showForm=true; $this->updatedFormCategory();
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
    }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formDescription','formType','formPriority','formSource','formProjectId','formRegionId','formCategoryId','formAssetId','formAssignedTo']); $this->formType='request'; $this->formPriority='medium'; $this->formSource='portal'; $this->suggestedEngineers=[]; }

    public function render()
    {
        $tickets = Ticket::with(['project','asset','assignee','creator'])->latest()->paginate(15);
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
