<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $assigned_to
 * @property int $created_by
 * @property string $title
 * @property string $status
 * @property string $priority
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class Task extends Model
{
    use HasFactory;

    // [REVIEW-FIX] C2: 状态常量
    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const PRIORITY_NOT_URGENT = 'not_urgent';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_URGENT = 'urgent';
    protected $fillable = [
        'project_id', 'title', 'description', 'assigned_to',
        'created_by', 'status', 'priority', 'due_date',
        'confirmed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    // ── 状态 ──────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending_confirmation' => __('待确认'),
            'in_progress'          => __('进行中'),
            'completed'            => __('已完成'),
            default                => __('未知'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_confirmation' => 'amber',
            'in_progress'          => 'sky',
            'completed'            => 'green',
            default                => 'zinc',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'not_urgent' => __('不紧急'),
            'normal'     => __('一般'),
            'urgent'     => __('紧急'),
            default      => __('未知'),
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'not_urgent' => 'zinc',
            'normal'     => 'sky',
            'urgent'     => 'red',
            default      => 'zinc',
        };
    }
}
