<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Livewire\Component;

class ProjectForm extends Component
{
    public ?Project $project = null;
    public bool $isEdit = false;

    // Form fields
    public string $title = '';
    public string $description = '';
    public int|string $category_id = '';
    public int|string $owner_id = '';
    public string $type = 'new';
    public string $progress = 'pending';
    public int $completion_percent = 0;
    public string $start_date = '';
    public string $end_date = '';
    public string $remark = '';

    protected function rules(): array
    {
        return [
            'title'              => 'required|string|max:200',
            'description'        => 'nullable|string|max:2000',
            'category_id'        => 'required|exists:project_categories,id',
            'owner_id'           => 'nullable|exists:users,id',
            'type'               => 'required|in:new,improved',
            'progress'           => 'required|in:pending,in_progress,paused,completed',
            'completion_percent' => 'integer|min:0|max:100',
            'start_date'         => 'nullable|date',
            'end_date'           => 'nullable|date|after_or_equal:start_date',
            'remark'             => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'title.required'     => '项目标题不能为空',
        'category_id.required' => '请选择项目分类',
        'end_date.after_or_equal' => '结束日期不能早于开始日期',
    ];

    public function mount(?Project $project = null): void
    {
        if ($project && $project->exists) {
            $this->isEdit = true;
            $this->project = $project;
            $this->fill([
                'title'              => $project->title,
                'description'        => $project->description ?? '',
                'category_id'        => $project->category_id,
                'owner_id'           => $project->owner_id ?? '',
                'type'               => $project->type,
                'progress'           => $project->progress,
                'completion_percent' => $project->completion_percent,
                'start_date'         => $project->start_date?->format('Y-m-d') ?? '',
                'end_date'           => $project->end_date?->format('Y-m-d') ?? '',
                'remark'             => $project->remark ?? '',
            ]);
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title'              => $this->title,
            'description'        => $this->description ?: null,
            'category_id'        => $this->category_id,
            'owner_id'           => $this->owner_id ?: null,
            'type'               => $this->type,
            'progress'           => $this->progress,
            'completion_percent' => $this->completion_percent,
            'start_date'         => $this->start_date ?: null,
            'end_date'           => $this->end_date ?: null,
            'remark'             => $this->remark ?: null,
        ];

        if ($this->isEdit) {
            $this->project->update($data);
            $this->project->logAction(auth()->id(), 'updated', $data);
            session()->flash('success', '项目已更新！');
            $this->redirect(route('projects.show', $this->project));
        } else {
            $data['created_by'] = auth()->id();
            $project = Project::create($data);
            $project->logAction(auth()->id(), 'created');
            // 创建者自动加入项目
            $project->members()->attach(auth()->id(), ['role' => 'lead']);
            session()->flash('success', '项目已创建！');
            $this->redirect(route('projects.show', $project));
        }
    }

    public function render()
    {
        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $title = $this->isEdit ? '编辑项目' : '创建项目';

        return view('livewire.projects.project-form', compact('categories', 'users'))
            ->layout('layouts.app', ['title' => $title]);
    }
}
