<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbTag extends Model
{
    protected $fillable = ['name', 'color', 'count'];

    public function articles()
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'kb_article_tag', 'tag_id', 'article_id');
    }
}
