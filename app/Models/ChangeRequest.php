<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequest extends Model
{
    protected $fillable = [
        'project_id', 'service_id', 'title', 'description',
        'type', 'risk', 'status',
        'requester_id', 'approver_id',
        'change_window_start', 'change_window_end',
        'rollback_plan', 'implemented_at',
    ];

    protected function casts(): array
    {
        return [
            'change_window_start' => 'datetime',
            'change_window_end'   => 'datetime',
            'implemented_at'      => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) { 'release'=>'发布','config'=>'配置变更','rollback'=>'回滚','hotfix'=>'紧急修复', default=>'未知' };
    }

    public function getRiskLabelAttribute(): string
    {
        return match($this->risk) { 'low'=>'低','medium'=>'中','high'=>'高','critical'=>'严重', default=>'未知' };
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk) { 'low'=>'green','medium'=>'sky','high'=>'amber','critical'=>'red', default=>'zinc' };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'=>'草稿','pending_approval'=>'待审批','approved'=>'已批准','rejected'=>'已拒绝',
            'in_progress'=>'执行中','completed'=>'已完成','rolled_back'=>'已回滚', default=>'未知'
        };
    }
}
