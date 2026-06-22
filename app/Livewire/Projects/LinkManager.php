<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectLink;
use Livewire\Component;

class LinkManager extends Component
{
    public Project $project;
    // [REVIEW-FIX] H1: 隐藏 project 模型，防止 Livewire 序列化到前端
    protected function getPropertyList(): array
    {
        return array_diff(parent::getPropertyList(), ['project']);
    }
    public bool $showLinkForm = false;
    public string $linkType = 'relates_to';
    public int|string $targetProjectId = '';
    public string $searchLink = '';
    public array $searchResults = [];

    protected $rules = [
        'linkType'        => 'required|in:blocks,relates_to,parent',
        'targetProjectId' => 'required|exists:projects,id',
    ];

    // [REVIEW-FIX-R1 #8 P1] 修复 fatal error：$messages 属性不能使用 __() 函数调用
    // 作为默认值（PHP 常量表达式限制）。改为 Livewire 支持的 messages() 方法。
    protected function messages(): array
    {
        return [
            'targetProjectId.required' => __('请选择一个项目'),
        ];
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    private function guard(): void
    {
        $user = auth()->user();
        if ($user->can('view all projects')) return;
        if ((int)$this->project->created_by === $user->id) return;
        if ($this->project->isLead($user->id)) return;
        abort(403);
    }

    public function updatedSearchLink(): void
    {
        $keyword = trim($this->searchLink);
        if (mb_strlen($keyword) < 2) {
            $this->searchResults = [];
            return;
        }

        // [REVIEW-FIX] P1.6: 转义 LIKE 通配符 % 和 _
        $escaped = addcslashes($keyword, '%_');
        $this->searchResults = Project::where('id', '!=', $this->project->id)
            ->where('title', 'like', "%{$escaped}%")
            ->limit(10)
            ->get(['id', 'title'])
            ->toArray();
    }

    public function selectTarget(int $id, string $title): void
    {
        $this->targetProjectId = $id;
        $this->searchLink = $title;
        $this->searchResults = [];
    }

    public function addLink(): void
    {
        $this->guard();
        $this->validate();

        $fromId = $this->project->id;
        $toId = (int) $this->targetProjectId;

        // 不能链接自身
        if ($fromId === $toId) {
            session()->flash('link_error', __('不能链接到自身。'));
            return;
        }

        // 重复检查
        $exists = ProjectLink::where('project_id', $fromId)
            ->where('target_id', $toId)
            ->where('link_type', $this->linkType)
            ->exists();

        if ($exists) {
            session()->flash('link_error', __('该关联已存在。'));
            return;
        }

        // blocks 循环检测
        if ($this->linkType === 'blocks' && ProjectLink::wouldCreateBlocksCycle($fromId, $toId)) {
            session()->flash('link_error', __('该阻断关系会造成循环依赖，不允许创建。'));
            return;
        }

        // parent：一个项目只能有一个父
        if ($this->linkType === 'parent') {
            $hasParent = ProjectLink::where('project_id', $fromId)
                ->where('link_type', 'parent')
                ->exists();
            if ($hasParent) {
                session()->flash('link_error', __('该项目已有父项目，不能重复设置。'));
                return;
            }
        }

        ProjectLink::create([
            'project_id' => $fromId,
            'target_id'  => $toId,
            'link_type'  => $this->linkType,
            'created_by' => auth()->id(),
        ]);

        session()->flash('link_success', __('项目关联已创建。'));
        $this->resetLinkForm();
    }

    // [REVIEW-FIX] P0.2: 只允许删除自己项目创建的出向链接，入向链接只读
    public function removeLink(int $linkId): void
    {
        $this->guard();
        $link = ProjectLink::where('project_id', $this->project->id)
            ->findOrFail($linkId);

        $link->delete();
        session()->flash('link_success', __('关联已解除。'));
    }

    public function resetLinkForm(): void
    {
        $this->showLinkForm = false;
        $this->reset(['linkType', 'targetProjectId', 'searchLink', 'searchResults']);
        $this->linkType = 'relates_to';
        $this->project->load('outgoingLinks.target', 'incomingLinks.project');
    }

    public function render()
    {
        $outgoing = $this->project->outgoingLinks()
            ->with('target')->get();

        $incoming = $this->project->incomingLinks()
            ->with('project')->get()
            ->filter(fn($l) => $l->project !== null);

        return view('livewire.projects.link-manager', compact('outgoing', 'incoming'));
    }
}
