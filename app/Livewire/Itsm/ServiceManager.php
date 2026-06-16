<?php

namespace App\Livewire\Itsm;

use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceManager extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formName = '';
    public int|string $formProjectId = '';
    public string $formType = 'custom';
    public string $formStatus = 'healthy';
    public string $formDescription = '';
    public int|string $formOwnerId = '';
    public string $formHealthUrl = '';
    public string $formTags = '';

    protected $rules = [
        'formName' => 'required|string|max:100',
        'formType' => 'required|string',
    ];

    public function save(): void
    {
        // [REVIEW-FIX] R12.2: 服务管理操作需权限检查
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', '没有服务管理权限');
            return;
        }
        $this->validate();
        $data = [
            'name' => $this->formName,
            'project_id' => $this->formProjectId ?: null,
            'type' => $this->formType,
            'status' => $this->formStatus,
            'description' => $this->formDescription ?: null,
            'owner_id' => $this->formOwnerId ?: null,
            'health_check_url' => $this->formHealthUrl ?: null,
            'tags' => $this->formTags ? array_map('trim', explode(',', $this->formTags)) : null,
        ];

        if ($this->editingId) {
            Service::findOrFail($this->editingId)->update($data);
        } else {
            Service::create($data);
        }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', '没有服务管理权限');
            return;
        }
        $s = Service::findOrFail($id);
        $this->editingId = $id;
        $this->formName = $s->name;
        $this->formProjectId = $s->project_id ?? '';
        $this->formType = $s->type;
        $this->formStatus = $s->status;
        $this->formDescription = $s->description ?? '';
        $this->formOwnerId = $s->owner_id ?? '';
        $this->formHealthUrl = $s->health_check_url ?? '';
        $this->formTags = $s->tags ? implode(', ', $s->tags) : '';
        $this->showForm = true;
    }

    public function delete(int $id): void {
        if (!auth()->user()->can("manage assets")) { session()->flash("error", "没有删除权限"); return; }
        // [REVIEW-FIX] N4: 删除服务前检查依赖关系
        $depCount = \App\Models\ServiceDependency::where('depends_on_id', $id)->count();
        $incidentCount = \App\Models\Incident::where('service_id', $id)->whereIn('status', ['open', 'investigating'])->count();
        $changeCount = \App\Models\ChangeRequest::where('service_id', $id)->whereNotIn('status', ['completed', 'rolled_back'])->count();
        if ($depCount > 0 || $incidentCount > 0 || $changeCount > 0) {
            session()->flash("error", "该服务被 {$depCount} 个服务依赖，有 {$incidentCount} 个活跃故障，{$changeCount} 个进行中变更，不能删除。");
            return;
        }
        Service::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->showForm = false; $this->editingId = null;
        $this->reset(['formName','formProjectId','formType','formStatus','formDescription','formOwnerId','formHealthUrl','formTags']);
        $this->formStatus = 'healthy'; $this->formType = 'custom';
    }

    public function render()
    {
        $services = Service::with(['project','owner'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $users = User::where('is_active', true)->orderBy('name')->get(['id','name']);
        return view('livewire.itsm.services', compact('services','projects','users'))
            ->layout('layouts.app', ['title' => '服务目录']);
    }
}
