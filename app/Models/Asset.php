<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_tag','name','type','category','brand','model','serial_number',
        'status','quantity','assigned_to','location','department',
        'purchase_date','warranty_expiry','notes',
    ];

    protected function casts(): array { return ['purchase_date'=>'date','warranty_expiry'=>'date']; }

    public function assignee() { return $this->belongsTo(User::class,'assigned_to'); }
    public function tickets() { return $this->hasMany(Ticket::class); }

    public function getStatusLabelAttribute(): string { return match($this->status) { 'in_use'=>'使用中','available'=>'空闲','repair'=>'维修中','retired'=>'已报废', default=>$this->status }; }
    public function getStatusColorAttribute(): string { return match($this->status) { 'in_use'=>'green','available'=>'sky','repair'=>'amber','retired'=>'zinc', default=>'zinc' }; }
    public function getTypeIconAttribute(): string { return match($this->type) { 'laptop'=>'💻','desktop'=>'🖥️','printer'=>'🖨️','switch'=>'🌐','server'=>'🗄️','monitor'=>'🖥️','software'=>'💿','license'=>'📄', default=>'📦' }; }

    public function getCategoryLabelAttribute(): string { return match($this->category) { 'fixed'=>'固定资产','non_fixed'=>'非固定资产','consumable'=>'损耗品', default=>$this->category }; }
    public function getCategoryColorAttribute(): string { return match($this->category) { 'fixed'=>'sky','non_fixed'=>'amber','consumable'=>'zinc', default=>'zinc' }; }
}
