<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    protected $fillable = ['project_id', 'name', 'git_repo', 'trigger', 'stages_config', 'is_active'];
    protected function casts(): array { return ['stages_config'=>'array', 'is_active'=>'boolean']; }
    public function project() { return $this->belongsTo(Project::class); }
    public function runs() { return $this->hasMany(PipelineRun::class)->latest(); }
}
