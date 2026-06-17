<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    use HasFactory;

    // [REVIEW-FIX] SP3.3: 创建 Environment 模型 — environments 表存在于 migration 但缺少模型
    protected $fillable = [
        'project_id',
        'name',
        'type',
        'host_url',
        'config_hash',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'config_hash' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
