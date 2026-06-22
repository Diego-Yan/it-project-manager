<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// [REVIEW-FIX] R15.4: 关系方法添加返回类型声明
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
        'username', 'source', 'ad_guid', 'department', 'position', 'phone',
        'is_active', 'last_login_at', 'locale',
        // AD 相关字段
        'ad_domain', 'ad_username', 'ad_display_name', 'ad_email',
        'ad_authenticated', 'ad_last_sync_at',
        // 企微/钉钉
        'wechat_userid', 'dingtalk_userid',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'ad_last_sync_at'   => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'ad_authenticated'  => 'boolean',
        ];
    }

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')
                    ->withPivot('role', 'assigned_at')
                    ->withTimestamps();
    }

    public function projectLogs(): HasMany
    {
        return $this->hasMany(ProjectLog::class);
    }

    public function expertiseCategories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCategory::class, 'user_categories', 'user_id', 'category_id');
    }

    public function getRoleNamesStringAttribute(): string
    {
        return $this->getRoleNames()->implode(', ') ?: __('无角色');
    }

    /**
     * 是否为 AD 域账号
     */
    public function isAdUser(): bool
    {
        return (bool) $this->ad_authenticated;
    }
}
