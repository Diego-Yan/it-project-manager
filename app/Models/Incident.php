<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'project_id', 'service_id', 'title', 'description',
        'severity', 'status', 'reported_by', 'assigned_to',
        'started_at', 'resolved_at', 'root_cause', 'resolution', 'postmortem_url',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function timeline(): HasMany { return $this->hasMany(IncidentTimeline::class)->latest('created_at'); }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) { 'P0'=>__('P0 紧急'),'P1'=>__('P1 严重'),'P2'=>__('P2 一般'),'P3'=>__('P3 轻微'),'P4'=>__('P4 建议'), default=>__('未知') };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) { 'P0'=>'red','P1'=>'amber','P2'=>'sky','P3'=>'zinc','P4'=>'zinc', default=>'zinc' };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open'=>__('未处理'),'investigating'=>__('调查中'),'mitigated'=>__('已缓解'),'resolved'=>__('已解决'),'closed'=>__('已关闭'), default=>__('未知')
        };
    }

    public function getMttrAttribute(): ?string
    {
        if (!$this->started_at || !$this->resolved_at) return null;
        $minutes = (int) $this->started_at->diffInMinutes($this->resolved_at);
        if ($minutes < 60) return __(':minutes 分钟', ['minutes' => $minutes]);
        return round($minutes/60,1) . __(' 小时');
    }
}
