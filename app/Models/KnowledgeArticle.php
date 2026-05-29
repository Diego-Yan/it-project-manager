<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    protected $fillable = ['title','content','category','tags','view_count','is_published','created_by'];
    protected function casts(): array { return ['tags'=>'array','is_published'=>'boolean']; }
    public function author() { return $this->belongsTo(User::class,'created_by'); }
    public function getCategoryLabelAttribute(): string { return match($this->category) { 'network'=>'网络','hardware'=>'硬件','software'=>'软件','account'=>'账号','printer'=>'打印','general'=>'通用', default=>$this->category }; }
}
