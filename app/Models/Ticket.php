<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// [REVIEW-FIX] R15.4: 关系方法添加返回类型声明
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'project_id','region_id','category_id','asset_id','title','description','type','priority',
        'status','source','assigned_to','created_by','resolved_by',
        'reported_for','user_confirmed_at',
        'resolution','sla_deadline','resolved_at','closed_at',
    ];

    protected function casts(): array
    {
        return ['user_confirmed_at' => 'datetime', 'sla_deadline'=>'datetime','resolved_at'=>'datetime','closed_at'=>'datetime']; // [REVIEW-FIX] M2
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function category(): BelongsTo { return $this->belongsTo(ProjectCategory::class, 'category_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class,'resolved_by'); }
    public function reportedFor(): BelongsTo { return $this->belongsTo(User::class,'reported_for'); }
    public function comments(): HasMany { return $this->hasMany(TicketComment::class)->latest(); }

    public function getTypeLabelAttribute(): string { return match($this->type) { 'incident'=>'故障','request'=>'请求','change'=>'变更','problem'=>'问题', default=>$this->type }; }
    public function getTypeColorAttribute(): string { return match($this->type) { 'incident'=>'red','request'=>'sky','change'=>'amber','problem'=>'violet', default=>'zinc' }; }
    public function getPriorityLabelAttribute(): string { return match($this->priority) { 'low'=>'低','medium'=>'中','high'=>'高','critical'=>'紧急', default=>$this->priority }; }
    public function getPriorityColorAttribute(): string { return match($this->priority) { 'low'=>'zinc','medium'=>'sky','high'=>'amber','critical'=>'red', default=>'zinc' }; }
    public function getStatusLabelAttribute(): string { return match($this->status) { 'open'=>'未处理','in_progress'=>'处理中','resolved'=>'已解决','closed'=>'已关闭', default=>$this->status }; }
    public function getSourceLabelAttribute(): string { return match($this->source) { // [REVIEW-FIX] R14.1: 补充 IM 平台来源标签
            'phone'=>'电话远程','email'=>'邮件沟通','portal'=>'自助报修','walk_in'=>'现场处理','im_wechat'=>'企业微信','im_dingtalk'=>'钉钉', default=>$this->source }; }

    public function isSlaBreached(): bool { return $this->sla_deadline && now()->gt($this->sla_deadline) && !in_array($this->status,['resolved','closed']); }

    // [REVIEW-FIX] C3: 轻量状态机 — 守卫工单生命周期转换
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const TRANSITIONS = [
        self::STATUS_OPEN        => [self::STATUS_IN_PROGRESS],
        self::STATUS_IN_PROGRESS => [self::STATUS_RESOLVED],
        self::STATUS_RESOLVED    => [self::STATUS_CLOSED],
    ];

    /** @return string[] 当前状态允许的目标状态 */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** 认领工单: open → in_progress */
    public function transitionToInProgress(int $assigneeId): bool
    {
        if (! $this->canTransitionTo(self::STATUS_IN_PROGRESS)) return false;
        return $this->update(['status' => self::STATUS_IN_PROGRESS, 'assigned_to' => $assigneeId]);
    }

    /** 解决工单: in_progress → resolved */
    public function transitionToResolved(int $resolverId): bool
    {
        if (! $this->canTransitionTo(self::STATUS_RESOLVED)) return false;
        return $this->update(['status' => self::STATUS_RESOLVED, 'resolved_by' => $resolverId, 'resolved_at' => now()]);
    }

    /** 关闭工单: resolved → closed */
    public function transitionToClosed(): bool
    {
        if (! $this->canTransitionTo(self::STATUS_CLOSED)) return false;
        return $this->update(['status' => self::STATUS_CLOSED, 'closed_at' => now()]);
    }
}
