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
        return match($this->type) { 'release'=>__('发布'),'config'=>__('配置变更'),'rollback'=>__('回滚'),'hotfix'=>__('紧急修复'), default=>__('未知') };
    }

    public function getRiskLabelAttribute(): string
    {
        return match($this->risk) { 'low'=>__('低'),'medium'=>__('中'),'high'=>__('高'),'critical'=>__('严重'), default=>__('未知') };
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk) { 'low'=>'green','medium'=>'sky','high'=>'amber','critical'=>'red', default=>'zinc' };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'=>__('草稿'),'pending_approval'=>__('待审批'),'approved'=>__('已批准'),'rejected'=>__('已拒绝'),
            'in_progress'=>__('执行中'),'completed'=>__('已完成'),'rolled_back'=>__('已回滚'), default=>__('未知')
        };
    }
}
