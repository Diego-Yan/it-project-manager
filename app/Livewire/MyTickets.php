<?php

namespace App\Livewire;

use App\Models\Ticket;
use Livewire\Component;
use Livewire\WithPagination;

class MyTickets extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public function render()
    {
        $user = auth()->user();

        $tickets = Ticket::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->with('asset')
            ->latest()
            ->paginate(15);

        $counts = [
            'open'         => Ticket::where('assigned_to', $user->id)->where('status', 'open')->count(),
            'in_progress'  => Ticket::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'resolved'     => Ticket::where('assigned_to', $user->id)->where('status', 'resolved')->count(),
        ];

        return view('livewire.my-tickets', compact('tickets', 'counts'))
            ->layout('layouts.app', ['title' => '我的工单']);
    }
}
