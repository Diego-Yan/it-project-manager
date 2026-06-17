<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// [REVIEW-FIX] R15.4: 关系方法添加返回类型声明
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $category_id
 * @property int|null $region_id
 * @property int $created_by
 * @property string $title
 * @property string $progress
 * @property string $type
 * @property string $urgency
 * @property string $importance
 * @property int $completion_percent
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 */
class Project extends Model
{
    use SoftDeletes, HasFactory;

    // [REVIEW-FIX] C2: 进度/类型/紧急度常量
    public const PROGRESS_PENDING    = 'pending';
    public const PROGRESS_IN_PROGRESS = 'in_progress';
    public const PROGRESS_PAUSED     = 'paused';
    public const PROGRESS_COMPLETED  = 'completed';

    public const TYPE_NEW       = 'new';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_UPGRADE   = 'upgrade';

    public const URGENCY_NORMAL = 'normal';
    public const URGENCY_URGENT = 'urgent';

    public const IMPORTANCE_NORMAL    = 'normal';
    public const IMPORTANCE_IMPORTANT = 'important';

    protected $fillable = [
        'category_id', 'region_id', 'created_by', 'owner_id',
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
            'completion_percent' => 'integer',
        ];
    }

    // ── 关联 ──────────────────────────────────────────────
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
                    ->withPivot('role', 'assigned_at');
    }

    public function leads(): BelongsToMany
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

    public function logs(): HasMany
    {
        return $this->hasMany(ProjectLog::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(ProjectLink::class, 'project_id');
    }

    public function incomingLinks(): HasMany
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

    public function webhooks(): HasMany
    {
        return $this->hasMany(WebhookConfig::class);
    }

    public function attachments(): HasMany
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
