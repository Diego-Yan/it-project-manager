<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableCatalog extends Model
{
    protected $table = 'consumable_catalog';
    protected $fillable = ['name', 'brand', 'unit', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
