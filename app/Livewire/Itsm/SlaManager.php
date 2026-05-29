<?php

namespace App\Livewire\Itsm;

use App\Models\Sla;
use Livewire\Component;

class SlaManager extends Component
{
    public bool $showForm = false; public ?int $editingId = null;
    public string $formName = '', $formPriority = 'medium'; public int $formResponse = 30, $formResolution = 240;
    public bool $formIsActive = true;

    protected $rules = ['formName'=>'required|max:100','formResponse'=>'required|integer|min:1','formResolution'=>'required|integer|min:1'];

    public function save(): void
    {
        $this->validate();
        $data = ['name'=>$this->formName,'priority'=>$this->formPriority,'response_minutes'=>$this->formResponse,'resolution_minutes'=>$this->formResolution,'is_active'=>$this->formIsActive];
        if ($this->editingId) { Sla::findOrFail($this->editingId)->update($data); }
        else { Sla::create($data); }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $s = Sla::findOrFail($id);
        $this->editingId=$id; $this->formName=$s->name; $this->formPriority=$s->priority;
        $this->formResponse=$s->response_minutes; $this->formResolution=$s->resolution_minutes;
        $this->formIsActive=$s->is_active; $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("manage slas")) return; Sla::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formPriority','formResponse','formResolution']); $this->formPriority='medium'; $this->formResponse=30; $this->formResolution=240; $this->formIsActive=true; }

    public function render()
    {
        $slas = Sla::orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->get();
        return view('livewire.itsm.slas', compact('slas'))
            ->layout('layouts.app', ['title' => 'SLA 管理']);
    }
}
