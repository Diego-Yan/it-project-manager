<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password',
        'username', 'ad_guid', 'department', 'position', 'phone',
        'is_active', 'last_login_at',
        // AD 相关字段
        'ad_domain', 'ad_username', 'ad_display_name', 'ad_email',
        'ad_authenticated', 'ad_last_sync_at',
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

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_user')
                    ->withPivot('role', 'assigned_at')
                    ->withTimestamps();
    }

    public function projectLogs()
    {
        return $this->hasMany(ProjectLog::class);
    }

    public function getRoleNamesStringAttribute(): string
    {
        return $this->getRoleNames()->implode(', ') ?: '无角色';
    }

    /**
     * 是否为 AD 域账号
     */
    public function isAdUser(): bool
    {
        return (bool) $this->ad_authenticated;
    }
}
