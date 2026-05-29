<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_tag','name','type','brand','model','serial_number',
        'status','assigned_to','location','department',
        'purchase_date','warranty_expiry','notes',
    ];

    protected function casts(): array { return ['purchase_date'=>'date','warranty_expiry'=>'date']; }

    public function assignee() { return $this->belongsTo(User::class,'assigned_to'); }
    public function tickets() { return $this->hasMany(Ticket::class); }

    public function getStatusLabelAttribute(): string { return match($this->status) { 'in_use'=>'使用中','available'=>'空闲','repair'=>'维修中','retired'=>'已报废', default=>$this->status }; }
    public function getStatusColorAttribute(): string { return match($this->status) { 'in_use'=>'green','available'=>'sky','repair'=>'amber','retired'=>'zinc', default=>'zinc' }; }
    public function getTypeIconAttribute(): string { return match($this->type) { 'laptop'=>'💻','desktop'=>'🖥️','printer'=>'🖨️','switch'=>'🌐','server'=>'🗄️','monitor'=>'🖥️','software'=>'💿','license'=>'📄', default=>'📦' }; }
}
