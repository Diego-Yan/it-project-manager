<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory;

    // [REVIEW-FIX] SP3.3: 创建 Pipeline 模型 — pipelines 表存在于 migration 但缺少模型
    protected $fillable = [
        'project_id',
        'name',
        'git_repo',
        'trigger',
        'stages_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stages_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(PipelineRun::class);
    }
}
