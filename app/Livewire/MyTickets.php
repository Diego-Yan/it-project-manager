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

    protected $rules = ['formTitle' => 'required|max:200'];

    public function createTicket(): void
    {
        $this->validate();
        Ticket::create([
            'title' => $this->formTitle,
            'description' => $this->formDescription ?: null,
            'type' => $this->formType,
            'priority' => $this->formPriority,
            'source' => $this->formSource,
            'created_by' => auth()->id(),
            'sla_deadline' => Sla::getDeadline($this->formPriority),
        ]);
        $this->showForm = false;
        $this->reset(['formTitle', 'formDescription', 'formType', 'formPriority']);
        $this->formType = 'request'; $this->formPriority = 'medium';
        session()->flash('success', '工单已创建');
    }

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
