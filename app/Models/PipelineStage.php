<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    public $timestamps = false;
    protected $fillable = ['pipeline_run_id','name','order','status','started_at','finished_at','duration_seconds','logs_url'];
    protected function casts(): array { return ['started_at'=>'datetime','finished_at'=>'datetime']; }
    public function run() { return $this->belongsTo(PipelineRun::class, 'pipeline_run_id'); }
    public function getStatusColorAttribute(): string { return match($this->status) { 'pending'=>'zinc','running'=>'sky','success'=>'green','failed'=>'red','skipped'=>'zinc', default=>'zinc' }; }
    public function getStageIconAttribute(): string { return match($this->name) { 'build'=>'🔨','test'=>'🧪','deploy-dev'=>'🛠️','deploy-staging'=>'📦','deploy-prod'=>'🚀', default=>'▶️' }; }
}
