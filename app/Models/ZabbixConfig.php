<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZabbixConfig extends Model
{
    protected $fillable = ['name', 'url', 'api_token', 'min_severity', 'poll_interval', 'is_active', 'last_poll_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_poll_at' => 'datetime'];
    }
}
