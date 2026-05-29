<?php

namespace App\Livewire\DevOps;

use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class EnvManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formName = 'dev', $formType = 'server', $formHostUrl = '', $formDescription = '';
    public int|string $formProjectId = '';

    protected $rules = ['formProjectId'=>'required|exists:projects,id'];

    public function save(): void
    {
        $this->validate();
        $data = ['project_id'=>$this->formProjectId,'name'=>$this->formName,'type'=>$this->formType,'host_url'=>$this->formHostUrl?:null,'description'=>$this->formDescription?:null];
        if ($this->editingId) { Environment::findOrFail($this->editingId)->update($data); }
        else { Environment::create($data); }
        $this->resetForm();
    }

    public function deploy(int $envId): void
    {
        $env = Environment::findOrFail($envId);
        $prevVersion = $env->latestDeployment?->version ?? 'v0.0.0';
        $newVersion = 'v' . now()->format('Y.m.d.Hi');
        Deployment::create(['environment_id'=>$envId,'version'=>$newVersion,'status'=>'success','deployed_by'=>auth()->id(),'deployed_at'=>now()]);
        session()->flash('success', "{$env->name} 部署成功: {$newVersion}");
    }

    public function rollback(int $envId): void
    {
        $env = Environment::findOrFail($envId);
        $deployments = $env->deployments()->where('status','success')->get();
        if ($deployments->count() < 2) { session()->flash('error','没有可回滚的版本'); return; }
        $prev = $deployments->skip(1)->first();
        $env->deployments()->latest()->first()->update(['status'=>'rolled_back']);
        Deployment::create(['environment_id'=>$envId,'version'=>$prev->version . '-rollback','status'=>'success','deployed_by'=>auth()->id(),'deployed_at'=>now()]);
        session()->flash('success', "已回滚到 {$prev->version}");
    }

    public function edit(int $id): void
    {
        $env = Environment::findOrFail($id);
        $this->editingId=$id; $this->formName=$env->name; $this->formType=$env->type;
        $this->formHostUrl=$env->host_url??''; $this->formDescription=$env->description??'';
        $this->formProjectId=$env->project_id; $this->showForm=true;
    }

    public function delete(int $id): void { Environment::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formType','formHostUrl','formDescription','formProjectId']); $this->formName='dev'; $this->formType='server'; }

    public function render()
    {
        $environments = Environment::with(['project','latestDeployment'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        return view('livewire.devops.environments', compact('environments','projects'))
            ->layout('layouts.app', ['title' => '环境管理']);
    }
}
