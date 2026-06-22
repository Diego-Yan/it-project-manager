<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApplication extends Model
{
    protected $fillable = ['project_id', 'user_id', 'message', 'status'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'  => __('待审批'),
            'approved' => __('已通过'),
            'rejected' => __('已拒绝'),
            default    => __('未知'),
        };
    }
}
