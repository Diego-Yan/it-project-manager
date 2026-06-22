<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCategory extends Model
{
    protected $fillable = ['name', 'type', 'description', 'color', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    // type 常量
    const TYPE_OPS = 'ops';
    const TYPE_DEV = 'dev';

    public static function typeLabel(string $type): string
    {
        return match($type) {
            self::TYPE_OPS => __('运维项目'),
            self::TYPE_DEV => __('开发项目'),
            default        => __('未知'),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabel($this->type);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'category_id');
    }

    public function getColorClassAttribute(): string
    {
        return match($this->color) {
            'sky'    => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
            'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300',
            'indigo' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
            'amber'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'red'    => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'green'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            default  => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300',
        };
    }
}
