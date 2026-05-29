<?php

namespace App\Livewire\DevOps;

use App\Models\Project;
use App\Models\Release;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class ReleaseManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formVersion = '', $formGitRef = '', $formGitRepo = '', $formChangelog = '';
    public int|string $formProjectId = '', $formServiceId = '';
    public string $formStatus = 'planned';

    protected $rules = ['formVersion' => 'required|string|max:50', 'formProjectId' => 'required|exists:projects,id'];

    public function save(): void
    {
        $this->validate();
        $data = ['project_id'=>$this->formProjectId, 'service_id'=>$this->formServiceId?:null, 'version'=>$this->formVersion, 'git_ref'=>$this->formGitRef?:null, 'git_repo'=>$this->formGitRepo?:null, 'changelog'=>$this->formChangelog?:null, 'status'=>$this->formStatus, 'deployed_by'=>auth()->id()];
        if ($this->editingId) { Release::findOrFail($this->editingId)->update($data); }
        else { Release::create($data); }
        $this->resetForm();
    }

    public function markStatus(int $id, string $status): void
    {
        $rel = Release::findOrFail($id);
        $rel->update(['status' => $status]);
        if (in_array($status, ['success','failed','rolled_back'])) {
            $rel->update(['deployed_at' => now()]);
        }
    }

    public function edit(int $id): void
    {
        $r = Release::findOrFail($id);
        $this->editingId=$id; $this->formVersion=$r->version; $this->formGitRef=$r->git_ref??'';
        $this->formGitRepo=$r->git_repo??''; $this->formChangelog=$r->changelog??'';
        $this->formProjectId=$r->project_id; $this->formServiceId=$r->service_id??'';
        $this->formStatus=$r->status; $this->showForm=true;
    }

    public function delete(int $id): void { Release::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formVersion','formGitRef','formGitRepo','formChangelog','formProjectId','formServiceId']); $this->formStatus='planned'; }

    public function render()
    {
        $releases = Release::with(['project','service','deployer'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $services = Service::orderBy('name')->get(['id','name']);
        return view('livewire.devops.releases', compact('releases','projects','services'))
            ->layout('layouts.app', ['title' => '发布管理']);
    }
}
