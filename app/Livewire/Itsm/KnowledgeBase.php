<?php

namespace App\Livewire\Itsm;

use App\Models\KbAttachment;
use App\Models\KbTag;
use App\Models\KnowledgeArticle;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class KnowledgeBase extends Component
{
    use WithPagination, WithFileUploads;

    public bool $showForm = false; public ?int $editingId = null; public ?int $viewId = null;
    public string $formTitle = '', $formContent = '', $formCategory = 'general';
    public array $selectedTagIds = [];
    public $uploadFile = null;

    public string $search = '';
    public string $filterTag = '';

    protected $rules = ['formTitle'=>'required|max:200','formContent'=>'required','uploadFile'=>'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,webp,mp4,mov|max:20480'];

    public function save(): void
    {
        $this->validate();
        $data = ['title'=>$this->formTitle,'content'=>$this->formContent,'category'=>$this->formCategory,'is_published'=>true,'created_by'=>auth()->id()];

        if ($this->editingId) {
            $article = KnowledgeArticle::findOrFail($this->editingId);
            $article->update($data);
        } else {
            $article = KnowledgeArticle::create($data);
        }

        // Sync tags — [REVIEW-FIX] I15: 批量更新标签计数，避免 N+1
        if (!empty($this->selectedTagIds)) {
            $article->kbTags()->sync($this->selectedTagIds);
            $tags = KbTag::whereIn('id', $this->selectedTagIds)->withCount('articles')->get();
            foreach ($tags as $tag) {
                $tag->update(['count' => $tag->articles_count]);
            }
        }

        // File upload
        if ($this->uploadFile) {
            $path = $this->uploadFile->store('kb-attachments', 'public');
            $mime = $this->uploadFile->getMimeType();
            KbAttachment::create([
                'article_id' => $article->id,
                                // [REVIEW-FIX] R8.6: 安全文件名 — 仅保留字母数字中文点号下划线横线
                'file_name' => preg_replace('/[^a-zA-Z0-9.\x{4e00}-\x{9fff}_-]/u', '_', $this->uploadFile->getClientOriginalName()),
                'file_path' => $path,
                'mime_type' => $mime,
                'file_size' => $this->uploadFile->getSize(),
            ]);
            $this->uploadFile = null;
        }

        $this->resetForm();
        session()->flash('kb_success', '文章已发布');
    }

    public function deleteAttachment(int $attId): void
    {
        $att = KbAttachment::findOrFail($attId);
        // 只有文章作者或管理员可删除附件
        if ($att->article->created_by != auth()->id() && !auth()->user()->can('edit knowledge')) return;
        \Storage::disk('public')->delete($att->file_path);
        $att->delete();
    }

    public function view(int $id): void
    {
        $this->viewId = $id;
        KnowledgeArticle::findOrFail($id)->increment('view_count');
    }

    public function edit(int $id): void
    {
        // [REVIEW-FIX] R12.8: 编辑文章前验证权限
        $article = KnowledgeArticle::findOrFail($id);
        if ($article->created_by != auth()->id() && !auth()->user()->can('edit knowledge')) {
            session()->flash('error', '只能编辑自己创建的知识库文章');
            return;
        }
        $a = KnowledgeArticle::with('kbTags')->findOrFail($id);
        $this->editingId=$id; $this->formTitle=$a->title; $this->formContent=$a->content; $this->formCategory=$a->category;
        $this->selectedTagIds = $a->kbTags->pluck('id')->toArray();
        $this->showForm=true;
    }

    public function delete(int $id): void { $a = KnowledgeArticle::findOrFail($id); if ($a->created_by != auth()->id() && !auth()->user()->can("edit knowledge")) return; $a->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formContent','formCategory','selectedTagIds','uploadFile']); $this->formCategory='general'; $this->selectedTagIds=[]; }

    public function render()
    {
        $articles = KnowledgeArticle::with(['author', 'attachments', 'kbTags'])
            ->when($this->search, function ($q) {
                // [REVIEW-FIX] I1: 转义 LIKE 通配符防止 %_ 被误匹配
                // [REVIEW-FIX] C2: orWhere 包裹在嵌套 where() 防止绕过 is_published 过滤
                $escaped = addcslashes($this->search, '%_');
                $q->where(function ($q2) use ($escaped) {
                    $q2->where('title', 'like', "%{$escaped}%")->orWhere('content', 'like', "%{$escaped}%");
                });
            })
            ->when($this->filterTag, fn($q)=>$q->whereHas('kbTags', fn($t)=>$t->where('kb_tags.id', $this->filterTag)))
            ->where('is_published',true)->latest()->paginate(12);

        $viewArticle = $this->viewId ? KnowledgeArticle::with(['attachments','kbTags','author'])->find($this->viewId) : null;
        $allTags = KbTag::orderBy('count','desc')->get();

        return view('livewire.itsm.knowledge', compact('articles','viewArticle','allTags'))
            ->layout('layouts.app', ['title' => '知识库']);
    }
}
