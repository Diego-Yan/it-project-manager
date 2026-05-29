<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
    protected $table = 'environments';
    protected $fillable = ['project_id','name','type','host_url','config_hash','description'];
    protected function casts(): array { return ['config_hash'=>'array']; }
    public function project() { return $this->belongsTo(Project::class); }
    public function deployments() { return $this->hasMany(Deployment::class)->latest(); }
    public function latestDeployment() { return $this->hasOne(Deployment::class)->latestOfMany(); }
    public function getTypeLabelAttribute(): string { return match($this->name) { 'dev'=>'开发','staging'=>'预发布','prod'=>'生产', default=>$this->name }; }
    public function getEnvColorAttribute(): string { return match($this->name) { 'dev'=>'sky','staging'=>'amber','prod'=>'red', default=>'zinc' }; }
}
