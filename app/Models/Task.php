<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── 状态 ──────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending_confirmation' => '待确认',
            'in_progress'          => '进行中',
            'completed'            => '已完成',
            default                => '未知',
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
            'not_urgent' => '不紧急',
            'normal'     => '一般',
            'urgent'     => '紧急',
            default      => '未知',
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
