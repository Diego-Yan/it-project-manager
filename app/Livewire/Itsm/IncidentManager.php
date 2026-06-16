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

    protected $rules = ['formTitle' => 'required|string|max:200', 'formProjectId' => 'required|exists:projects,id'];

    public function save(): void
    {
        // [REVIEW-FIX] R11.2: 操作权限检查
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', '没有故障管理权限');
            return;
        }
        $this->validate();
        $data = ['project_id'=>$this->formProjectId, 'service_id'=>$this->formServiceId?:null, 'title'=>$this->formTitle, 'severity'=>$this->formSeverity, 'description'=>$this->formDescription?:null, 'reported_by'=>auth()->id(), 'assigned_to'=>$this->formAssignedTo?:null, 'status'=>'open', 'started_at'=>now()];
        if ($this->editingId) {
            Incident::findOrFail($this->editingId)->update($data);
        } else {
            $inc = Incident::create($data);
            // [REVIEW-FIX] M6: 手动设置 created_at（IncidentTimeline $timestamps=false）
            IncidentTimeline::create(['incident_id'=>$inc->id, 'user_id'=>auth()->id(), 'action'=>'created', 'description'=>'创建故障工单', 'created_at'=>now()]);
        }
        $this->resetForm();
    }

    public function addTimeline(int $id, string $action): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', '没有故障管理权限');
            return;
        }
        $allowedActions = ['investigating', 'mitigated', 'resolved', 'commented'];
        if (!in_array($action, $allowedActions)) return;

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
            session()->flash('error', '没有故障管理权限');
            return;
        }
        $inc = Incident::findOrFail($id);
        // [REVIEW-FIX] I6: 只允许从 resolved 关闭，mitigated 需先 resolve
        if ($inc->status !== 'resolved') return;
        $inc->update(['status'=>'closed']);
        IncidentTimeline::create(['incident_id'=>$id, 'user_id'=>auth()->id(), 'action'=>'closed', 'description'=>'关闭故障', 'created_at'=>now()]);
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage incidents')) {
            session()->flash('error', '没有故障管理权限');
            return;
        }
        $inc = Incident::findOrFail($id);
        $this->editingId=$id; $this->formTitle=$inc->title; $this->formSeverity=$inc->severity;
        $this->formDescription=$inc->description??''; $this->formProjectId=$inc->project_id;
        $this->formServiceId=$inc->service_id??''; $this->formAssignedTo=$inc->assigned_to??'';
        $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("manage incidents")) { session()->flash("error", "没有删除权限"); return; } Incident::findOrFail($id)->delete(); }
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
            ->layout('layouts.app', ['title' => '故障管理']);
    }
}
