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
        'color'       => 'required|in:red,orange,amber,yellow,green,cyan,sky,blue,indigo,violet,purple' // [REVIEW-FIX] R3.3: 颜色对齐 Seeder,
    ];

    protected function messages(): array
    {
        return [
            'name.required' => __('请填写分类名称'),
            'type.required' => __('请选择所属项目类型'),
        ];
    }

    public function save(): void
    {
        // [REVIEW-FIX] SP6.1: 根据操作类型检查对应权限 — 编辑用 edit，新建用 create
        $requiredPerm = $this->editingId ? 'edit categories' : 'create categories';
        if (!auth()->user()->can($requiredPerm)) {
            session()->flash('error', __('没有分类管理权限'));
            return;
        }
        $this->validate();
        if ($this->editingId) {
            // [REVIEW-FIX] M1: findOrFail 防止删除后编辑导致 NPE
            ProjectCategory::findOrFail($this->editingId)->update([
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
        if (!auth()->user()->can('edit categories')) {
            session()->flash('error', __('没有分类编辑权限'));
            return;
        }
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
        if (!auth()->user()->can('delete categories')) abort(403);

        $cat = ProjectCategory::findOrFail($id);
        if ($cat->projects()->count() > 0) {
            session()->flash('error', __('分类「:name」下有 :count 个项目，请先移走项目再删除分类。', ['name' => $cat->name, 'count' => $cat->projects()->count()]));
            return;
        }
        $cat->delete();
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
            ->layout('layouts.app', ['title' => __('项目分类')]);
    }
}
