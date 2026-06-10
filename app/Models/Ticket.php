<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'project_id','region_id','category_id','asset_id','title','description','type','priority',
        'status','source','assigned_to','created_by','resolved_by',
        'reported_for','user_confirmed_at',
        'resolution','sla_deadline','resolved_at','closed_at',
    ];

    protected function casts(): array
    {
        return ['sla_deadline'=>'datetime','resolved_at'=>'datetime','closed_at'=>'datetime'];
    }

    public function project() { return $this->belongsTo(Project::class); }
    public function region() { return $this->belongsTo(Region::class); }
    public function category() { return $this->belongsTo(ProjectCategory::class, 'category_id'); }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function assignee() { return $this->belongsTo(User::class,'assigned_to'); }
    public function creator() { return $this->belongsTo(User::class,'created_by'); }
    public function resolver() { return $this->belongsTo(User::class,'resolved_by'); }
    public function reportedFor() { return $this->belongsTo(User::class,'reported_for'); }
    public function comments() { return $this->hasMany(TicketComment::class)->latest(); }

    public function getTypeLabelAttribute(): string { return match($this->type) { 'incident'=>'故障','request'=>'请求','change'=>'变更','problem'=>'问题', default=>$this->type }; }
    public function getTypeColorAttribute(): string { return match($this->type) { 'incident'=>'red','request'=>'sky','change'=>'amber','problem'=>'violet', default=>'zinc' }; }
    public function getPriorityLabelAttribute(): string { return match($this->priority) { 'low'=>'低','medium'=>'中','high'=>'高','critical'=>'紧急', default=>$this->priority }; }
    public function getPriorityColorAttribute(): string { return match($this->priority) { 'low'=>'zinc','medium'=>'sky','high'=>'amber','critical'=>'red', default=>'zinc' }; }
    public function getStatusLabelAttribute(): string { return match($this->status) { 'open'=>'未处理','in_progress'=>'处理中','resolved'=>'已解决','closed'=>'已关闭', default=>$this->status }; }
    public function getSourceLabelAttribute(): string { return match($this->source) { 'phone'=>'电话远程','email'=>'邮件沟通','portal'=>'自助报修','walk_in'=>'现场处理', default=>$this->source }; }

    public function isSlaBreached(): bool { return $this->sla_deadline && now()->gt($this->sla_deadline) && !in_array($this->status,['resolved','closed']); }
}
