<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectApplication extends Model
{
    protected $fillable = ['project_id', 'user_id', 'message', 'status'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'  => '待审批',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            default    => '未知',
        };
    }
}
