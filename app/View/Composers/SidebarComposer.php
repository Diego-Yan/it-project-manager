<?php

namespace App\View\Composers;

use App\Models\Asset;
use App\Models\Incident;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (!$user) return;

        // [REVIEW-FIX] P0.1: 6次独立COUNT合并为1次缓存查询，TTL=5分钟
        $cacheKey = "sidebar_counts:{$user->id}";
        $counts = Cache::remember($cacheKey, 300, function () use ($user) {
            return [
                'sidebarPendingTasks'     => Task::where('assigned_to', $user->id)->where('status', 'pending_confirmation')->count(),
                'sidebarOpenTickets'      => Ticket::where('assigned_to', $user->id)->whereIn('status', ['open', 'in_progress'])->count(),
                'sidebarProxyPending'     => Ticket::where('reported_for', $user->id)->whereNull('user_confirmed_at')->count(),
                'sidebarWarrantySoon'     => Asset::where('assigned_to', $user->id)->whereNotNull('warranty_expiry')->where('warranty_expiry', '<=', now()->addDays(30))->where('status', '!=', 'retired')->count(),
                'sidebarOpenIncidents'    => Incident::whereIn('status', ['open', 'investigating'])->count(),
                'sidebarTotalOpenTickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            ];
        });

        $view->with($counts);
    }

    /**
     * [REVIEW-FIX] P0.1: 清除指定用户的侧边栏缓存（工单/任务状态变更时调用）
     */
    public static function flushForUser(int $userId): void
    {
        Cache::forget("sidebar_counts:{$userId}");
    }
}
