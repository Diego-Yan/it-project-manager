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
        ChangeRequest::findOrFail($id)->update(['status' => 'pending_approval']);
    }

    public function approve(int $id): void
    {
        $cr = ChangeRequest::findOrFail($id);
        $cr->update(['status' => 'approved', 'approver_id' => auth()->id()]);
    }

    public function reject(int $id): void
    {
        ChangeRequest::findOrFail($id)->update(['status' => 'rejected', 'approver_id' => auth()->id()]);
    }

    public function startImplement(int $id): void
    {
        ChangeRequest::findOrFail($id)->update(['status' => 'in_progress']);
    }

    public function complete(int $id): void
    {
        ChangeRequest::findOrFail($id)->update(['status' => 'completed', 'implemented_at' => now()]);
    }

    public function rollback(int $id): void
    {
        ChangeRequest::findOrFail($id)->update(['status' => 'rolled_back', 'implemented_at' => now()]);
    }

    public function edit(int $id): void
    {
        $cr = ChangeRequest::findOrFail($id);
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
