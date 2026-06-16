<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Task;
use App\Models\User;
use Livewire\Component;

class ProjectForm extends Component
{
    public ?Project $project = null;
    // [REVIEW-FIX] H1: 隐藏 project 模型，防止 Livewire 序列化到前端
    protected function getPropertyList(): array
    {
        return array_diff(parent::getPropertyList(), ['project']);
    }
    public bool $isEdit = false;

    // Form fields
    public string $title = '';
    public string $description = '';
    public int|string $category_id = '';
    public int|string $region_id = '';
    public int|string $owner_id = '';
    public string $type = 'new';
    public string $progress = 'pending';
    public string $urgency = 'normal';
    public string $importance = 'normal';
    public int $completion_percent = 0;
    public string $start_date = '';
    public string $end_date = '';
    public string $remark = '';

    // 内联任务
    public array $inlineTasks = [];
    public string $newTaskTitle = '';
    public string $newTaskPriority = 'normal';

    // ── 内联任务操作 ──────────────────────────────────────

    public function addInlineTask(): void
    {
        $title = trim($this->newTaskTitle);
        if (empty($title)) return;

        $this->inlineTasks[] = [
            'title'    => $title,
            'priority' => $this->newTaskPriority,
        ];
        $this->newTaskTitle = '';
        $this->newTaskPriority = 'normal';
    }

    public function removeInlineTask(int $index): void
    {
        unset($this->inlineTasks[$index]);
        $this->inlineTasks = array_values($this->inlineTasks);
    }

    protected function rules(): array
    {
        return [
            'title'              => 'required|string|max:200',
            'description'        => 'nullable|string|max:2000',
            'category_id'        => 'required|exists:project_categories,id',
            'region_id'          => 'required|exists:regions,id',
            'owner_id'           => 'nullable|exists:users,id',
            'type'               => 'required|in:new,improved,issue',
            'progress'           => 'required|in:pending,in_progress,paused,completed',
            'urgency'            => 'required|in:not_urgent,normal,urgent',
            'importance'         => 'required|in:normal,important,very_important',
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
                'region_id'          => $project->region_id ?? '',
                'owner_id'           => $project->owner_id ?? '',
                'type'               => $project->type,
                'progress'           => $project->progress,
                'urgency'            => $project->urgency ?? 'normal',
                'importance'         => $project->importance ?? 'normal',
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
            'region_id'          => $this->region_id ?: null,
            'owner_id'           => $this->owner_id ?: null,
            'type'               => $this->type,
            'progress'           => $this->progress,
            'urgency'            => $this->urgency,
            'importance'         => $this->importance,
            'completion_percent' => $this->completion_percent,
            'start_date'         => $this->start_date ?: null,
            'end_date'           => $this->end_date ?: null,
            'remark'             => $this->remark ?: null,
        ];

        if ($this->isEdit) {
            // [REVIEW-FIX] R17.2: 格式化 Carbon 对象为字符串后再 diff，避免类型不匹配误判
            $original = $this->project->only(array_keys($data));
            // Carbon 日期对象需转为与表单一致的 Y-m-d 字符串
            $dateKeys = ['start_date', 'end_date', 'actual_end_date'];
            foreach ($dateKeys as $k) {
                if (isset($original[$k]) && $original[$k] instanceof \Carbon\Carbon) {
                    $original[$k] = $original[$k]->format('Y-m-d');
                }
            }
            $changes = array_diff_assoc($data, $original);
            $this->project->update($data);
            if (!empty($changes)) {
                $this->project->logAction(auth()->id(), 'updated', $changes);
            }
            session()->flash('success', '项目已更新！');
            $this->redirect(route('projects.show', $this->project));
        } else {
            $data['created_by'] = auth()->id();
            $project = Project::create($data);
            $project->logAction(auth()->id(), 'created');
            $project->members()->attach(auth()->id(), ['role' => 'lead']);

            // 创建内联任务
            foreach ($this->inlineTasks as $taskData) {
                Task::create([
                    'project_id' => $project->id,
                    'title'      => $taskData['title'],
                    'priority'   => $taskData['priority'],
                    'created_by' => auth()->id(),
                    'status'     => 'in_progress',
                ]);
            }

            session()->flash('success', '项目已创建！');
            $this->redirect(route('projects.show', $project));
        }
    }

    public function render()
    {
        $categories = ProjectCategory::where('is_active', true)->orderBy('sort_order')->get();
        $regions = \App\Models\Region::orderBy('sort_order')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $title = $this->isEdit ? '编辑项目' : '创建项目';

        return view('livewire.projects.project-form', compact('categories', 'users', 'regions'))
            ->layout('layouts.app', ['title' => $title]);
    }
}
