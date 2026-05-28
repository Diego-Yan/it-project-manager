<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['project_id', 'user_id', 'action', 'changes', 'comment', 'created_at'];

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created'        => '创建了项目',
            'updated'        => '更新了项目',
            'status_changed' => '变更了进度',
            'member_added'   => '添加了成员',
            'member_removed' => '移除了成员',
            'file_uploaded'  => '上传了附件',
            'file_deleted'   => '删除了附件',
            default          => $this->action,
        };
    }
}
