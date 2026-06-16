<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KbTag extends Model
{
    protected $fillable = ['name', 'color', 'count'];
    protected function casts(): array { return ['count'=>'integer']; }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'kb_article_tag', 'tag_id', 'article_id');
    }
}
