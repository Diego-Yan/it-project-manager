<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function project() { return $this->belongsTo(Project::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function timeline() { return $this->hasMany(IncidentTimeline::class)->latest('created_at'); }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) { 'P0'=>'P0 紧急','P1'=>'P1 严重','P2'=>'P2 一般','P3'=>'P3 轻微','P4'=>'P4 建议', default=>'未知' };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) { 'P0'=>'red','P1'=>'amber','P2'=>'sky','P3'=>'zinc','P4'=>'zinc', default=>'zinc' };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open'=>'未处理','investigating'=>'调查中','mitigated'=>'已缓解','resolved'=>'已解决','closed'=>'已关闭', default=>'未知'
        };
    }

    public function getMttrAttribute(): ?string
    {
        if (!$this->started_at || !$this->resolved_at) return null;
        $minutes = (int) $this->started_at->diffInMinutes($this->resolved_at);
        if ($minutes < 60) return "{$minutes} 分钟";
        return round($minutes/60,1) . ' 小时';
    }
}
