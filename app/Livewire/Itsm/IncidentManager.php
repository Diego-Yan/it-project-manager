<?php

namespace App\Livewire\Itsm;

use App\Models\Incident;
use App\Models\IncidentTimeline;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class IncidentManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formTitle = '', $formSeverity = 'P3', $formDescription = '';
    public int|string $formProjectId = '', $formServiceId = '', $formAssignedTo = '';
    public string $timelineNote = '';
    public ?int $viewTimelineId = null;

    // [REVIEW-FIX-R6 #2 P2] 补全缺失的字段验证规则：
    // 原 rules 仅验证 formTitle 和 formProjectId，但 save() 写入 DB 的字段包括
    // formSeverity/formDescription/formServiceId/formAssignedTo。
    // 这些字段完全无验证 → 可注入任意 severity 字符串、超长描述。
    protected $rules = [
        'formTitle'      => 'required|string|max:200',
        'formProjectId'  => 'required|exists:projects,id',
        'formServiceId'  => 'nullable|exists:services,id',
        'formSeverity'   => 'required|in:P1,P2,P3,P4',
        'formDescription'=> 'nullable|string|max:5000',
        'formAssignedTo' => 'nullable|exists:users,id',
    ];

    public function save(): void
    {
        // [REVIEW-FIX] R11.2: 操作权限检查
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有故障管理权限'));
            return;
        }
        $this->validate();
        $data = ['project_id'=>$this->formProjectId, 'service_id'=>$this->formServiceId?:null, 'title'=>$this->formTitle, 'severity'=>$this->formSeverity, 'description'=>$this->formDescription?:null, 'reported_by'=>auth()->id(), 'assigned_to'=>$this->formAssignedTo?:null, 'status'=>'open', 'started_at'=>now()];
        if ($this->editingId) {
            Incident::findOrFail($this->editingId)->update($data);
        } else {
            // [REVIEW-FIX] SP2.3: 创建故障 + 时间线条目包裹事务防半成状态
            $inc = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                $incident = Incident::create($data);
                // [REVIEW-FIX] M6: 手动设置 created_at（IncidentTimeline $timestamps=false）
                IncidentTimeline::create(['incident_id'=>$incident->id, 'user_id'=>auth()->id(), 'action'=>'created', 'description'=>__('创建故障工单'), 'created_at'=>now()]);
                return $incident;
            });
        }
        $this->resetForm();
    }

    public function addTimeline(int $id, string $action): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有故障管理权限'));
            return;
        }
        $allowedActions = ['investigating', 'mitigated', 'resolved', 'commented'];
        if (!in_array($action, $allowedActions)) return;

        // [REVIEW-FIX-R7 #5 P3] timelineNote 长度验证：原代码直接写入 DB 无任何长度限制，
        // 用户可注入超长文本。补充 5000 字限制，与 KnowledgeBase/TicketBoard 等组件一致。
        if (mb_strlen($this->timelineNote) > 5000) {
            session()->flash('error', __('时间线备注不能超过 5000 字'));
            return;
        }

        IncidentTimeline::create(['incident_id'=>$id, 'user_id'=>auth()->id(), 'action'=>$action, 'description'=>$this->timelineNote, 'created_at'=>now()]);
        $statusMap = ['investigating'=>'investigating', 'mitigated'=>'mitigated', 'resolved'=>'resolved'];
        if (isset($statusMap[$action])) {
            $inc = Incident::findOrFail($id);
            $inc->update(['status'=>$statusMap[$action]]);
            if ($action === 'resolved') $inc->update(['resolved_at'=>now()]);
        }
        $this->timelineNote = '';
    }

    public function close(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有故障管理权限'));
            return;
        }
        $inc = Incident::findOrFail($id);
        // [REVIEW-FIX] I6: 只允许从 resolved 关闭，mitigated 需先 resolve
        if ($inc->status !== 'resolved') return;
        $inc->update(['status'=>'closed']);
        IncidentTimeline::create(['incident_id'=>$id, 'user_id'=>auth()->id(), 'action'=>'closed', 'description'=>__('关闭故障'), 'created_at'=>now()]);
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', __('没有故障管理权限'));
            return;
        }
        $inc = Incident::findOrFail($id);
        $this->editingId=$id; $this->formTitle=$inc->title; $this->formSeverity=$inc->severity;
        $this->formDescription=$inc->description??''; $this->formProjectId=$inc->project_id;
        $this->formServiceId=$inc->service_id??''; $this->formAssignedTo=$inc->assigned_to??'';
        $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("manage incidents")) { session()->flash("error", __("没有删除权限")); return; } Incident::findOrFail($id)->delete(); }
    public function toggleTimeline(int $id): void { $this->viewTimelineId = $this->viewTimelineId === $id ? null : $id; }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formSeverity','formDescription','formProjectId','formServiceId','formAssignedTo']); $this->formSeverity='P3'; }

    public function render()
    {
        $incidents = Incident::with(['project','service','assignee','reporter'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $services = Service::orderBy('name')->get(['id','name']);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        $openCount = Incident::whereIn('status',['open','investigating'])->count();
        $timelines = [];
        if ($this->viewTimelineId) {
            $timelines[$this->viewTimelineId] = Incident::find($this->viewTimelineId)?->timeline()->with('user')->get() ?? collect();
        }
        return view('livewire.itsm.incidents', compact('incidents','projects','services','users','openCount','timelines'))
            ->layout('layouts.app', ['title' => __('故障管理')]);
    }
}
