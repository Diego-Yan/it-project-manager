<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'project_id', 'service_id', 'change_request_id',
        'version', 'git_ref', 'git_repo', 'changelog',
        'status', 'deployed_by', 'deployed_at',
    ];

    protected function casts(): array
    {
        return ['deployed_at' => 'datetime'];
    }

    public function project() { return $this->belongsTo(Project::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function changeRequest() { return $this->belongsTo(ChangeRequest::class); }
    public function deployer() { return $this->belongsTo(User::class, 'deployed_by'); }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'planned'=>'计划中','deploying'=>'部署中','success'=>'部署成功','failed'=>'部署失败','rolled_back'=>'已回滚', default=>'未知'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'planned'=>'zinc','deploying'=>'sky','success'=>'green','failed'=>'red','rolled_back'=>'amber', default=>'zinc'
        };
    }
}
