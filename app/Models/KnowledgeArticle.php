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
    protected $fillable = ['title','content','category','view_count','is_published','created_by']; // [REVIEW-FIX] SP3.2: 移除冗余 tags JSON 列（已由 kbTags() 关系替代） // [REVIEW-FIX] R4.5: 移除 summary/is_vectorized/vector_ids（DB中不存在的字段）

    // [REVIEW-FIX] SP3.2: 移除 'tags'=>'array' cast — tags 已由 kb_article_tag 多对多关系替代，JSON列已废弃
    protected function casts(): array
    {
        return ['is_published'=>'boolean', 'view_count'=>'integer'];
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
