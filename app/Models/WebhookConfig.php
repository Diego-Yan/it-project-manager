<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

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

    // [REVIEW-FIX] C4: 加密 webhook URL（常含 API token 作为查询参数）
    public function getUrlAttribute(?string $value): ?string
    {
        if (empty($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return $value; // 兼容未加密旧数据
        }
    }

    public function setUrlAttribute(?string $value): void
    {
        if (empty($value)) { $this->attributes['url'] = null; return; }
        try {
            Crypt::decryptString($value); // 已是加密值
            $this->attributes['url'] = $value;
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $this->attributes['url'] = Crypt::encryptString($value);
        }
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'wechat'   => __('企业微信'),
            'dingtalk' => __('钉钉'),
            'custom'   => __('自定义'),
            default    => __('未知'),
        };
    }
}
