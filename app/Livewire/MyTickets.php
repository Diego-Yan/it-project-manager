<?php

namespace App\Livewire;

use App\Models\Sla;
use App\Models\Ticket;
use Livewire\Component;
use Livewire\WithPagination;

class MyTickets extends Component
{
    use WithPagination;

    public string $filterStatus = '';
    public bool $showForm = false;
    public string $formTitle = '', $formDescription = '', $formType = 'request', $formPriority = 'medium', $formSource = 'portal';
    public int|string $formRegionId = '';

    protected $rules = ['formTitle' => 'required|max:200', 'formRegionId' => 'required|exists:regions,id'];

    public function createTicket(): void
    {
        $this->validate();
        Ticket::create([
            'title' => $this->formTitle,
            'description' => $this->formDescription ?: null,
            'type' => $this->formType,
            'priority' => $this->formPriority,
            'source' => $this->formSource,
            'region_id' => $this->formRegionId,
            'created_by' => auth()->id(),
            'sla_deadline' => Sla::getDeadline($this->formPriority),
        ]);
        $this->showForm = false;
        $this->reset(['formTitle', 'formDescription', 'formType', 'formPriority', 'formRegionId']);
        $this->formType = 'request'; $this->formPriority = 'medium';
        session()->flash('success', '工单已创建');
    }

    // 用户确认代填工单
    public function confirmProxy(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->reported_for == auth()->id() && !$ticket->user_confirmed_at) {
            $ticket->update(['user_confirmed_at' => now()]);
            session()->flash('success', '工单已确认');
        }
    }

    public function render()
    {
        $user = auth()->user();

        $tickets = Ticket::where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('reported_for', $user->id);
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->with(['asset', 'creator'])
            ->latest()
            ->paginate(15);

        $counts = [
            'open'         => Ticket::where('assigned_to', $user->id)->where('status', 'open')->count(),
            'in_progress'  => Ticket::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'resolved'     => Ticket::where('assigned_to', $user->id)->where('status', 'resolved')->count(),
        ];

        $regions = \App\Models\Region::orderBy('sort_order')->get();
        return view('livewire.my-tickets', compact('tickets', 'counts', 'regions'))
            ->layout('layouts.app', ['title' => '我的工单']);
    }
}
