<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = ['environment_id','release_id','version','status','deployed_by','deployed_at','logs_url'];
    protected function casts(): array { return ['deployed_at'=>'datetime']; }
    public function environment() { return $this->belongsTo(Environment::class); }
    public function release() { return $this->belongsTo(Release::class); }
    public function deployer() { return $this->belongsTo(User::class, 'deployed_by'); }
    public function getStatusColorAttribute(): string { return match($this->status) { 'deploying'=>'sky','success'=>'green','failed'=>'red','rolled_back'=>'amber', default=>'zinc' }; }
}
