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
        'urgency', 'importance',
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

    public function leads()
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withPivot('role', 'assigned_at')
                    ->wherePivot('role', 'lead');
    }

    public function isLead(int $userId): bool
    {
        return $this->leads()->where('user_id', $userId)->exists();
    }

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function logs()
    {
        return $this->hasMany(ProjectLog::class)->latest();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function applications()
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function outgoingLinks()
    {
        return $this->hasMany(ProjectLink::class, 'project_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(ProjectLink::class, 'target_id');
    }

    public function getBlockingProjectsAttribute()
    {
        return ProjectLink::where('target_id', $this->id)
            ->where('link_type', 'blocks')
            ->with('project')
            ->get()
            ->pluck('project');
    }

    public function getBlockedProjectsAttribute()
    {
        return ProjectLink::where('project_id', $this->id)
            ->where('link_type', 'blocks')
            ->with('target')
            ->get()
            ->pluck('target');
    }

    public function getRelatedProjectsAttribute()
    {
        $outgoing = ProjectLink::where('project_id', $this->id)
            ->where('link_type', 'relates_to')
            ->with('target')
            ->get()
            ->pluck('target');

        $incoming = ProjectLink::where('target_id', $this->id)
            ->where('link_type', 'relates_to')
            ->with('project')
            ->get()
            ->pluck('project');

        return $outgoing->merge($incoming)->unique('id');
    }

    public function getChildProjectsAttribute()
    {
        return ProjectLink::where('target_id', $this->id)
            ->where('link_type', 'parent')
            ->with('project')
            ->get()
            ->pluck('project');
    }

    public function getParentProjectAttribute()
    {
        $link = ProjectLink::where('project_id', $this->id)
            ->where('link_type', 'parent')
            ->with('target')
            ->first();

        return $link?->target;
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
            'issue'    => '异常',
            default    => '未知',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'new'      => 'blue',
            'improved' => 'green',
            'issue'    => 'red',
            default    => 'zinc',
        };
    }

    public function getUrgencyLabelAttribute(): string
    {
        return match($this->urgency) {
            'not_urgent' => '不紧急',
            'normal'     => '一般',
            'urgent'     => '紧急',
            default      => '未知',
        };
    }

    public function getUrgencyColorAttribute(): string
    {
        return match($this->urgency) {
            'not_urgent' => 'zinc',
            'normal'     => 'sky',
            'urgent'     => 'red',
            default      => 'zinc',
        };
    }

    public function getImportanceLabelAttribute(): string
    {
        return match($this->importance) {
            'normal'        => '一般',
            'important'     => '重要',
            'very_important'=> '非常重要',
            default         => '未知',
        };
    }

    public function getImportanceColorAttribute(): string
    {
        return match($this->importance) {
            'normal'        => 'zinc',
            'important'     => 'amber',
            'very_important'=> 'red',
            default         => 'zinc',
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
