<?php

namespace App\Livewire\Itsm;

use App\Models\KnowledgeArticle;
use Livewire\Component;
use Livewire\WithPagination;

class KnowledgeBase extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null; public ?int $viewId = null;
    public string $formTitle = '', $formContent = '', $formCategory = 'general', $formTags = '';
    public string $search = '';

    protected $rules = ['formTitle'=>'required|max:200','formContent'=>'required'];

    public function save(): void
    {
        $this->validate();
        $data = ['title'=>$this->formTitle,'content'=>$this->formContent,'category'=>$this->formCategory,'tags'=>$this->formTags?array_map('trim',explode(',',$this->formTags)):null,'is_published'=>true,'created_by'=>auth()->id()];
        if ($this->editingId) { KnowledgeArticle::findOrFail($this->editingId)->update($data); }
        else { KnowledgeArticle::create($data); }
        $this->resetForm();
    }

    public function view(int $id): void
    {
        $this->viewId = $id;
        KnowledgeArticle::findOrFail($id)->increment('view_count');
    }

    public function edit(int $id): void
    {
        $a = KnowledgeArticle::findOrFail($id);
        $this->editingId=$id; $this->formTitle=$a->title; $this->formContent=$a->content; $this->formCategory=$a->category; $this->formTags=$a->tags?implode(', ',$a->tags):'';
        $this->showForm=true;
    }

    public function delete(int $id): void { $a = KnowledgeArticle::findOrFail($id); if ($a->created_by != auth()->id() && !auth()->user()->can("view all projects")) return; $a->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formContent','formCategory','formTags']); $this->formCategory='general'; }

    public function render()
    {
        $articles = KnowledgeArticle::with('author')
            ->when($this->search, fn($q)=>$q->where('title','like',"%{$this->search}%")->orWhere('content','like',"%{$this->search}%"))
            ->where('is_published',true)->latest()->paginate(15);
        $viewArticle = $this->viewId ? KnowledgeArticle::find($this->viewId) : null;
        return view('livewire.itsm.knowledge', compact('articles','viewArticle'))
            ->layout('layouts.app', ['title' => '知识库']);
    }
}
