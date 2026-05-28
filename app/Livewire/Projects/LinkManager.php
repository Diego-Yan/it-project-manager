<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\ProjectLink;
use Livewire\Component;

class LinkManager extends Component
{
    public Project $project;
    public bool $showLinkForm = false;
    public string $linkType = 'relates_to';
    public int|string $targetProjectId = '';
    public string $searchLink = '';
    public array $searchResults = [];

    protected $rules = [
        'linkType'        => 'required|in:blocks,relates_to,parent',
        'targetProjectId' => 'required|exists:projects,id',
    ];

    protected $messages = [
        'targetProjectId.required' => '请选择一个项目',
    ];

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

        $this->searchResults = Project::where('id', '!=', $this->project->id)
            ->where('title', 'like', "%{$keyword}%")
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
            session()->flash('link_error', '不能链接到自身。');
            return;
        }

        // 重复检查
        $exists = ProjectLink::where('project_id', $fromId)
            ->where('target_id', $toId)
            ->where('link_type', $this->linkType)
            ->exists();

        if ($exists) {
            session()->flash('link_error', '该关联已存在。');
            return;
        }

        // blocks 循环检测
        if ($this->linkType === 'blocks' && ProjectLink::wouldCreateBlocksCycle($fromId, $toId)) {
            session()->flash('link_error', '该阻断关系会造成循环依赖，不允许创建。');
            return;
        }

        // parent：一个项目只能有一个父
        if ($this->linkType === 'parent') {
            $hasParent = ProjectLink::where('project_id', $fromId)
                ->where('link_type', 'parent')
                ->exists();
            if ($hasParent) {
                session()->flash('link_error', '该项目已有父项目，不能重复设置。');
                return;
            }
        }

        ProjectLink::create([
            'project_id' => $fromId,
            'target_id'  => $toId,
            'link_type'  => $this->linkType,
            'created_by' => auth()->id(),
        ]);

        session()->flash('link_success', '项目关联已创建。');
        $this->resetLinkForm();
    }

    public function removeLink(int $linkId): void
    {
        $this->guard();
        $link = ProjectLink::where('project_id', $this->project->id)
            ->orWhere('target_id', $this->project->id)
            ->findOrFail($linkId);

        $link->delete();
        session()->flash('link_success', '关联已解除。');
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
