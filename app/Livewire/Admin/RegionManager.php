<?php

namespace App\Livewire\Admin;

use App\Models\Region;
use Livewire\Component;

class RegionManager extends Component
{
    public string $formName = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    protected $rules = ['formName' => 'required|string|max:50|unique:regions,name'];

    public function save(): void
    {
        $rules = $this->editingId
            ? ['formName' => 'required|max:50|unique:regions,name,'.$this->editingId]
            : $this->rules;
        $this->validate($rules);

        if ($this->editingId) {
            Region::findOrFail($this->editingId)->update(['name' => $this->formName]);
        } else {
            Region::create(['name' => $this->formName, 'sort_order' => Region::count() + 1]);
        }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $r = Region::findOrFail($id);
        $this->editingId = $id;
        $this->formName = $r->name;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        if (!auth()->user()->can('view categories')) return;
        $region = Region::findOrFail($id);
        // Don't allow deleting if region has projects or tickets
        $projCount = \App\Models\Project::where('region_id', $id)->count();
        $ticketCount = \App\Models\Ticket::where('region_id', $id)->count();
        if ($projCount > 0 || $ticketCount > 0) {
            session()->flash('error', "地区「{$region->name}」下有 {$projCount} 个项目、{$ticketCount} 个工单，不能删除。");
            return;
        }
        $region->delete();
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->formName = '';
    }

    public function render()
    {
        $regions = Region::orderBy('sort_order')->get();
        return view('livewire.admin.region-manager', compact('regions'))
            ->layout('layouts.app', ['title' => '地区管理']);
    }
}
