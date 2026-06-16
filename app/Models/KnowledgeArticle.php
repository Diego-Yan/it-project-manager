<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeArticle extends Model
{
    use HasFactory;
    protected $fillable = ['title','content','category','tags','view_count','is_published','created_by']; // [REVIEW-FIX] R4.5: 移除 summary/is_vectorized/vector_ids（DB中不存在的字段）

    protected function casts(): array
    {
        return ['tags'=>'array', 'is_published'=>'boolean']; // [REVIEW-FIX] R4.5: 移除 vector_ids/is_vectorized
    }

    public function author(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function attachments(): HasMany { return $this->hasMany(KbAttachment::class, 'article_id'); }
    public function kbTags(): BelongsToMany { return $this->belongsToMany(KbTag::class, 'kb_article_tag', 'article_id', 'tag_id'); }

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

