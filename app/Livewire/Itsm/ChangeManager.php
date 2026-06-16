<?php

namespace App\Livewire\Itsm;

use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class ChangeManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formTitle = '', $formType = 'config', $formRisk = 'low', $formDescription = '', $formRollbackPlan = '';
    public int|string $formProjectId = '', $formServiceId = '';
    public string $formWindowStart = '', $formWindowEnd = '';

    protected $rules = ['formTitle' => 'required|string|max:200', 'formProjectId' => 'required|exists:projects,id'];

    public function save(): void
    {
        // [REVIEW-FIX] C5: 创建/编辑变更只需要 approve changes 权限（view changes 是只读）
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有变更管理权限');
            return;
        }
        $this->validate();
        $data = [
            'project_id' => $this->formProjectId, 'service_id' => $this->formServiceId ?: null,
            'title' => $this->formTitle, 'type' => $this->formType, 'risk' => $this->formRisk,
            'description' => $this->formDescription ?: null, 'rollback_plan' => $this->formRollbackPlan ?: null,
            'requester_id' => auth()->id(), 'status' => 'draft',
            'change_window_start' => $this->formWindowStart ?: null, 'change_window_end' => $this->formWindowEnd ?: null,
        ];
        if ($this->editingId) { ChangeRequest::findOrFail($this->editingId)->update($data); }
        else { ChangeRequest::create($data); }
        $this->resetForm();
    }

    public function submitForApproval(int $id): void
    {
        // [REVIEW-FIX] C5: 提交审批是写操作，需要 approve changes 非只读 view changes
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有变更管理权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if (!in_array($cr->status, ['draft', 'rejected'])) return;
        $cr->update(['status' => 'pending_approval']);
    }

    // [REVIEW-FIX] H2: approve/reject 加权限检查
    public function approve(int $id): void
    {
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有审批变更的权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if ($cr->status !== 'pending_approval') return;
        // [REVIEW-FIX] I5: 不能批准自己的变更（职责分离）
        if ((int) $cr->requester_id === auth()->id()) {
            session()->flash('error', '不能批准自己提交的变更，请由其他审批人处理。');
            return;
        }
        $cr->update(['status' => 'approved', 'approver_id' => auth()->id()]);
    }

    // [REVIEW-FIX] H2: approve/reject 加权限检查
    public function reject(int $id): void
    {
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有审批变更的权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if ($cr->status !== 'pending_approval') return;
        $cr->update(['status' => 'rejected', 'approver_id' => auth()->id()]);
    }

    // [REVIEW-FIX] H2: 实施/完成/回滚加权限检查
    public function startImplement(int $id): void
    {
        // [REVIEW-FIX] R11.3: 已有 can('approve changes') 检查，保留
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有实施变更的权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if ($cr->status !== 'approved') return;
        $cr->update(['status' => 'in_progress']);
    }

    // [REVIEW-FIX] H2: 实施/完成/回滚加权限检查
    public function complete(int $id): void
    {
        // [REVIEW-FIX] R11.3: 已有 can('approve changes') 检查，保留
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有完成变更的权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if ($cr->status !== 'in_progress') return;
        $cr->update(['status' => 'completed', 'implemented_at' => now()]);
    }

    // [REVIEW-FIX] H2: 实施/完成/回滚加权限检查
    public function rollback(int $id): void
    {
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有回滚变更的权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if (!in_array($cr->status, ['in_progress', 'completed'])) return;
        $cr->update(['status' => 'rolled_back', 'implemented_at' => now()]);
    }

    public function edit(int $id): void
    {
        // [REVIEW-FIX] SP2.1: 编辑变更需权限检查 + 仅允许编辑 draft/rejected 状态
        if (!auth()->user()->can('approve changes')) {
            session()->flash('error', '没有变更管理权限');
            return;
        }
        $cr = ChangeRequest::findOrFail($id);
        if (!in_array($cr->status, ['draft', 'rejected'])) {
            session()->flash('error', '只能编辑草稿或已拒绝的变更。');
            return;
        }
        $this->editingId=$id; $this->formTitle=$cr->title; $this->formProjectId=$cr->project_id;
        $this->formServiceId=$cr->service_id??''; $this->formType=$cr->type; $this->formRisk=$cr->risk;
        $this->formDescription=$cr->description??''; $this->formRollbackPlan=$cr->rollback_plan??'';
        $this->formWindowStart=$cr->change_window_start?->format('Y-m-d\TH:i')??'';
        $this->formWindowEnd=$cr->change_window_end?->format('Y-m-d\TH:i')??'';
        $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("approve changes")) { session()->flash("error", "没有删除权限"); return; } ChangeRequest::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formProjectId','formServiceId','formType','formRisk','formDescription','formRollbackPlan','formWindowStart','formWindowEnd']); $this->formType='config'; $this->formRisk='low'; }

    public function render()
    {
        $changes = ChangeRequest::with(['project','service','requester','approver'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $services = Service::orderBy('name')->get(['id','name']);
        return view('livewire.itsm.changes', compact('changes','projects','services'))
            ->layout('layouts.app', ['title' => '变更管理']);
    }
}
