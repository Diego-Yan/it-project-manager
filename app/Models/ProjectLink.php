<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLink extends Model
{
    protected $fillable = ['project_id', 'target_id', 'link_type', 'created_by'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'target_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLinkTypeLabelAttribute(): string
    {
        return match($this->link_type) {
            'blocks'     => __('阻断'),
            'relates_to' => __('关联'),
            'parent'     => __('父项目'),
            default      => __('未知'),
        };
    }

    public function getLinkTypeDirectionLabelAttribute(): string
    {
        return match($this->link_type) {
            'blocks'     => __('→ 阻断'),
            'relates_to' => __('↔ 关联'),
            'parent'     => __('← 父项目'),
            default      => '',
        };
    }

    /**
     * Check whether adding a link of type 'blocks' from $from to $to would create a cycle.
     * Uses a simple DFS through existing blocks links.
     */
    public static function wouldCreateBlocksCycle(int $from, int $to): bool
    {
        if ($from === $to) return true;

        // [REVIEW-FIX] R13.1: hash 键替代 in_array — O(n²)→O(n)
        $visited = [];
        $stack = [$to];

        while (!empty($stack)) {
            $current = array_pop($stack);
            if ($current === $from) return true;
            if (isset($visited[$current])) continue;
            $visited[$current] = true;

            $nextIds = self::where('link_type', 'blocks')
                ->where('project_id', $current)
                ->pluck('target_id')
                ->toArray();

            foreach ($nextIds as $nid) {
                $stack[] = $nid;
            }
        }

        return false;
    }
}
