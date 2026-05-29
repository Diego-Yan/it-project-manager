<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sla extends Model
{
    protected $fillable = ['name','priority','response_minutes','resolution_minutes','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }

    public static function getDeadline(string $priority): ?\Carbon\Carbon
    {
        $sla = self::where('priority',$priority)->where('is_active',true)->first();
        return $sla ? now()->addMinutes($sla->resolution_minutes) : null;
    }
}
