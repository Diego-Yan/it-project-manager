<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDependency extends Model
{
    protected $fillable = ['service_id', 'depends_on_id', 'type'];

    public function service() { return $this->belongsTo(Service::class, 'service_id'); }
    public function dependsOn() { return $this->belongsTo(Service::class, 'depends_on_id'); }
}
