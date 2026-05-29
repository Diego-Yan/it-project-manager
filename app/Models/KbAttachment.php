<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KbAttachment extends Model
{
    protected $fillable = ['article_id', 'file_name', 'file_path', 'mime_type', 'file_size', 'preview_type'];

    public function article() { return $this->belongsTo(KnowledgeArticle::class, 'article_id'); }

    public function getFileSizeHumanAttribute(): string
    {
        $s = $this->file_size;
        if ($s < 1024) return "{$s} B";
        if ($s < 1048576) return round($s/1024,1).' KB';
        return round($s/1048576,1).' MB';
    }

    public function getPreviewTypeAttribute($value): string
    {
        if ($value) return $value;
        // auto-detect from mime_type
        return match(true) {
            str_starts_with($this->mime_type, 'image/') => 'image',
            $this->mime_type === 'application/pdf' => 'pdf',
            str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'document') => 'office',
            str_contains($this->mime_type, 'presentation') || str_contains($this->mime_type, 'powerpoint') => 'office',
            str_contains($this->mime_type, 'spreadsheet') || str_contains($this->mime_type, 'excel') => 'office',
            str_contains($this->mime_type, 'video/') => 'video',
            default => 'download',
        };
    }
}
