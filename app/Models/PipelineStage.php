<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStage extends Model
{
    use HasFactory;

    // [REVIEW-FIX] SP3.3: 创建 PipelineStage 模型 — pipeline_stages 表存在于 migration 但缺少模型
    protected $fillable = [
        'pipeline_run_id',
        'name',
        'order',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'logs_url',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function pipelineRun(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class);
    }
}
