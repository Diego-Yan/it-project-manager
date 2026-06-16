<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'project_id', 'name', 'type', 'status', 'description',
        'owner_id', 'health_check_url', 'health_check_interval',
        'last_health_check_at', 'tags',
    ];

    protected function casts(): array
    {
        return [
            'tags'                 => 'array',
            'health_check_interval' => 'integer',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function dependencies(): HasMany { return $this->hasMany(ServiceDependency::class); }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) { 'healthy'=>'健康','degraded'=>'降级','down'=>'宕机','maintenance'=>'维护中', default=>'未知' };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) { 'healthy'=>'green','degraded'=>'amber','down'=>'red','maintenance'=>'zinc', default=>'zinc' };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) { 'web'=>'🌐','database'=>'🗄️','cache'=>'⚡','queue'=>'📨','storage'=>'💾','api'=>'🔌', default=>'🔧' };
    }
}
