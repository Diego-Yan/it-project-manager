<?php

namespace App\Livewire\Categories;

use App\Models\ProjectCategory;
use Livewire\Component;

class CategoryManager extends Component
{
    public string $name = '';
    public string $type = 'ops';
    public string $description = '';
    public string $color = 'red';
    public bool $showForm = false;
    public ?int $editingId = null;

    protected $rules = [
        'name'        => 'required|string|max:50',
        'type'        => 'required|in:ops,dev',
        'description' => 'nullable|string|max:200',
        'color'       => 'required|in:red,orange,yellow,green,cyan,blue,purple',
    ];

    protected $messages = [
        'name.required' => '请填写分类名称',
        'type.required' => '请选择所属项目类型',
    ];

    public function save(): void
    {
        $this->validate();
        if ($this->editingId) {
            ProjectCategory::find($this->editingId)->update([
                'name'        => $this->name,
                'type'        => $this->type,
                'description' => $this->description,
                'color'       => $this->color,
            ]);
        } else {
            ProjectCategory::create([
                'name'        => $this->name,
                'type'        => $this->type,
                'description' => $this->description,
                'color'       => $this->color,
            ]);
        }
        $this->reset(['name', 'type', 'description', 'color', 'showForm', 'editingId']);
        $this->color = 'red';
        $this->type  = 'ops';
    }

    public function edit(int $id): void
    {
        $cat = ProjectCategory::findOrFail($id);
        $this->editingId   = $id;
        $this->name        = $cat->name;
        $this->type        = $cat->type;
        $this->description = $cat->description ?? '';
        $this->color       = $cat->color;
        $this->showForm    = true;
    }

    public function delete(int $id): void
    {
        ProjectCategory::findOrFail($id)->delete();
    }

    public function render()
    {
        // 按类型分组展示
        $opsCategories = ProjectCategory::withCount('projects')
            ->where('type', 'ops')
            ->orderBy('sort_order')
            ->get();

        $devCategories = ProjectCategory::withCount('projects')
            ->where('type', 'dev')
            ->orderBy('sort_order')
            ->get();

        return view('livewire.categories.category-manager', compact('opsCategories', 'devCategories'))
            ->layout('layouts.app', ['title' => '项目分类']);
    }
}
