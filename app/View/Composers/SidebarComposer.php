<?php

namespace App\View\Composers;

use App\Models\Asset;
use App\Models\Incident;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (!$user) return;

        $view->with([
            'sidebarPendingTasks'     => Task::where('assigned_to', $user->id)->where('status', 'pending_confirmation')->count(),
            'sidebarOpenTickets'      => Ticket::where('assigned_to', $user->id)->whereIn('status', ['open', 'in_progress'])->count(),
            'sidebarProxyPending'     => Ticket::where('reported_for', $user->id)->whereNull('user_confirmed_at')->count(),
            'sidebarWarrantySoon'     => Asset::where('assigned_to', $user->id)->whereNotNull('warranty_expiry')->where('warranty_expiry', '<=', now()->addDays(30))->where('status', '!=', 'retired')->count(),
            'sidebarOpenIncidents'    => Incident::whereIn('status', ['open', 'investigating'])->count(),
            'sidebarTotalOpenTickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
        ]);
    }
}
