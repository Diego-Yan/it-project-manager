<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineRun extends Model
{
    protected $fillable = ['pipeline_id','commit_sha','branch','status','triggered_by','started_at','finished_at','logs_url'];
    protected function casts(): array { return ['started_at'=>'datetime','finished_at'=>'datetime']; }
    public function pipeline() { return $this->belongsTo(Pipeline::class); }
    public function triggerUser() { return $this->belongsTo(User::class, 'triggered_by'); }
    public function stages() { return $this->hasMany(PipelineStage::class)->orderBy('order'); }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) { 'pending'=>'zinc','running'=>'sky','success'=>'green','failed'=>'red','cancelled'=>'amber', default=>'zinc' };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->finished_at) return null;
        $s = (int) $this->started_at->diffInSeconds($this->finished_at);
        return $s < 120 ? "{$s}s" : round($s/60).'m';
    }
}
