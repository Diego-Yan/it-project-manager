<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookConfig extends Model
{
    protected $fillable = ['project_id', 'name', 'url', 'type', 'events', 'is_active'];

    protected function casts(): array
    {
        return [
            'events'   => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'wechat'   => '企业微信',
            'dingtalk' => '钉钉',
            'custom'   => '自定义',
            default    => '未知',
        };
    }
}
