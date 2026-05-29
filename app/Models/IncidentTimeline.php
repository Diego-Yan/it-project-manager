<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentTimeline extends Model
{
    public $timestamps = false;
    protected $fillable = ['incident_id', 'user_id', 'action', 'description', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function incident() { return $this->belongsTo(Incident::class); }
    public function user() { return $this->belongsTo(User::class); }
}
