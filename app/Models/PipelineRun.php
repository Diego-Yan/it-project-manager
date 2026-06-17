<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineRun extends Model
{
    use HasFactory;

    // [REVIEW-FIX] SP3.3: 创建 PipelineRun 模型 — pipeline_runs 表存在于 migration 但缺少模型
    protected $fillable = [
        'pipeline_id',
        'commit_sha',
        'branch',
        'status',
        'triggered_by',
        'started_at',
        'finished_at',
        'logs_url',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class);
    }
}
