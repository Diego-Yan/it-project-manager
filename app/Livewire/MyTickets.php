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
            // [REVIEW-FIX] I4: 仅自建工单自动确认，代填工单需被代填人确认
            'user_confirmed_at' => now(),
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
        if ((int)$ticket->reported_for === auth()->id() && !$ticket->user_confirmed_at) { // [REVIEW-FIX] R15.5
            $ticket->update(['user_confirmed_at' => now()]);
            // [REVIEW-FIX] I6: 刷新侧边栏代理待确认计数
            \App\View\Composers\SidebarComposer::flushForUser(auth()->id());
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
            ->with(['asset', 'creator', 'region'])
            ->latest()
            ->paginate(15);

        // [REVIEW-FIX] R15.6: 3次独立 COUNT → 1次 GROUP BY（同 R3.5 优化模式）
        $countsRaw = Ticket::where('assigned_to', $user->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
            ")->first();
        $counts = [
            'open'        => (int) ($countsRaw->open ?? 0),
            'in_progress' => (int) ($countsRaw->in_progress ?? 0),
            'resolved'    => (int) ($countsRaw->resolved ?? 0),
        ];

        $regions = \App\Models\Region::orderBy('sort_order')->get();
        return view('livewire.my-tickets', compact('tickets', 'counts', 'regions'))
            ->layout('layouts.app', ['title' => '我的工单']);
    }
}
