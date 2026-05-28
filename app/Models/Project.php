<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'created_by', 'owner_id',
        'title', 'description', 'type', 'progress',
        'completion_percent', 'start_date', 'end_date',
        'actual_end_date', 'remark',
    ];

    protected function casts(): array
    {
        return [
            'start_date'      => 'date',
            'end_date'        => 'date',
            'actual_end_date' => 'date',
        ];
    }

    // ── 关联 ──────────────────────────────────────────────
    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withPivot('role', 'assigned_at');
    }

    public function logs()
    {
        return $this->hasMany(ProjectLog::class)->latest();
    }

    public function attachments()
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    // ── 辅助方法 ──────────────────────────────────────────
    public function getProgressLabelAttribute(): string
    {
        return match($this->progress) {
            'pending'     => '未开始',
            'in_progress' => '进行中',
            'paused'      => '已暂停',
            'completed'   => '已完成',
            default       => '未知',
        };
    }

    public function getProgressColorAttribute(): string
    {
        return match($this->progress) {
            'pending'     => 'zinc',
            'in_progress' => 'sky',
            'paused'      => 'amber',
            'completed'   => 'green',
            default       => 'zinc',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'new'      => '新增',
            'improved' => '改善',
            default    => '未知',
        };
    }

    public function isOverdue(): bool
    {
        return $this->end_date
            && $this->end_date->isPast()
            && $this->progress !== 'completed';
    }

    // ── 日志记录 ──────────────────────────────────────────
    public function logAction(int $userId, string $action, array $changes = [], string $comment = ''): void
    {
        $this->logs()->create([
            'user_id' => $userId,
            'action'  => $action,
            'changes' => $changes ?: null,
            'comment' => $comment ?: null,
        ]);
    }
}
