<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    protected $fillable = ['title','content','summary','category','tags','view_count','is_published','created_by','is_vectorized','vector_ids'];

    protected function casts(): array
    {
        return ['tags'=>'array', 'vector_ids'=>'array', 'is_published'=>'boolean', 'is_vectorized'=>'boolean'];
    }

    public function author() { return $this->belongsTo(User::class,'created_by'); }
    public function attachments() { return $this->hasMany(KbAttachment::class, 'article_id'); }
    public function kbTags() { return $this->belongsToMany(KbTag::class, 'kb_article_tag', 'article_id', 'tag_id'); }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'network'=>'网络','hardware'=>'硬件','software'=>'软件','account'=>'账号','printer'=>'打印','general'=>'通用',
            default=>$this->category
        };
    }

    public function getPreviewUrlAttribute(): ?string
    {
        $first = $this->attachments()->first();
        return $first ? asset('storage/'.$first->file_path) : null;
    }
}

