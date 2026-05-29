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

    public function delete(int $id): void { Service::findOrFail($id)->delete(); }

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
