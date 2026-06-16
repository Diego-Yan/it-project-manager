<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDependency extends Model
{
    protected $fillable = ['service_id', 'depends_on_id', 'type'];

    public function service(): BelongsTo { return $this->belongsTo(Service::class, 'service_id'); }
    public function dependsOn(): BelongsTo { return $this->belongsTo(Service::class, 'depends_on_id'); }
}
