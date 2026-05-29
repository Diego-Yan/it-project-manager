<?php

namespace App\Livewire\DevOps;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\PipelineStage;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class PipelineManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formName = '', $formGitRepo = '', $formTrigger = 'push';
    public int|string $formProjectId = '';
    public ?int $viewRunId = null;

    protected $rules = ['formName'=>'required|max:100','formProjectId'=>'required|exists:projects,id'];

    public function save(): void
    {
        $this->validate();
        $data = ['project_id'=>$this->formProjectId,'name'=>$this->formName,'git_repo'=>$this->formGitRepo?:null,'trigger'=>$this->formTrigger];
        if ($this->editingId) { Pipeline::findOrFail($this->editingId)->update($data); }
        else { Pipeline::create($data); }
        $this->resetForm();
    }

    public function runPipeline(int $id): void
    {
        $pipeline = Pipeline::findOrFail($id);
        $defaultStages = ['build','test','deploy-dev','deploy-staging'];
        $stages = $pipeline->stages_config ?: $defaultStages;

        $run = PipelineRun::create(['pipeline_id'=>$id,'status'=>'running','triggered_by'=>auth()->id(),'started_at'=>now()]);

        foreach ($stages as $i => $stage) {
            PipelineStage::create(['pipeline_run_id'=>$run->id,'name'=>is_array($stage)?$stage['name']:$stage,'order'=>$i,'status'=>'pending']);
        }

        // Auto-complete for demo: mark stages as success
        foreach ($run->stages as $stage) {
            $stage->update(['status'=>'success','started_at'=>now(),'finished_at'=>now(),'duration_seconds'=>rand(5,60)]);
        }
        $run->update(['status'=>'success','finished_at'=>now()]);
    }

    public function edit(int $id): void
    {
        $p = Pipeline::findOrFail($id);
        $this->editingId=$id; $this->formName=$p->name; $this->formGitRepo=$p->git_repo??'';
        $this->formTrigger=$p->trigger; $this->formProjectId=$p->project_id; $this->showForm=true;
    }

    public function delete(int $id): void { Pipeline::findOrFail($id)->delete(); }
    public function toggleRun(int $id): void { $this->viewRunId = $this->viewRunId === $id ? null : $id; }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formGitRepo','formTrigger','formProjectId']); $this->formTrigger='push'; }

    public function render()
    {
        $pipelines = Pipeline::with(['project','runs'])->latest()->paginate(10);
        $projects = Project::orderBy('title')->get(['id','title']);
        $runDetails = $this->viewRunId ? PipelineRun::with('stages')->find($this->viewRunId) : null;
        return view('livewire.devops.pipelines', compact('pipelines','projects','runDetails'))
            ->layout('layouts.app', ['title' => 'CI/CD 流水线']);
    }
}
