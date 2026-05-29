<?php

namespace App\Livewire\Itsm;

use App\Models\Asset;
use App\Models\Project;
use App\Models\Region;
use App\Models\Sla;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TicketBoard extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formTitle = '', $formDescription = '', $formType = 'request', $formPriority = 'medium', $formSource = 'portal';
    public int|string $formProjectId = '', $formRegionId = '', $formAssetId = '', $formAssignedTo = '';
    public string $newComment = ''; public ?int $viewTicketId = null;

    protected $rules = ['formTitle'=>'required|max:200', 'formRegionId'=>'required|exists:regions,id'];

    public function save(): void
    {
        $this->validate();
        $data = [
            'title'=>$this->formTitle,'description'=>$this->formDescription?:null,
            'type'=>$this->formType,'priority'=>$this->formPriority,'source'=>$this->formSource,
            'project_id'=>$this->formProjectId?:null,'asset_id'=>$this->formAssetId?:null,
            'region_id'=>$this->formRegionId?:null,
            'assigned_to'=>$this->formAssignedTo?:null,'created_by'=>auth()->id(),
            'sla_deadline'=>Sla::getDeadline($this->formPriority),
        ];
        if ($this->editingId) { Ticket::findOrFail($this->editingId)->update($data); }
        else { Ticket::create($data); }
        $this->resetForm();
    }

    public function assign(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'open') return;
        $ticket->update(['assigned_to'=>auth()->id(),'status'=>'in_progress']);
    }

    public function resolve(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'in_progress' || $ticket->assigned_to != auth()->id()) return;
        $ticket->update(['status'=>'resolved','resolved_by'=>auth()->id(),'resolved_at'=>now()]);
    }

    public function close(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->status !== 'resolved') return;
        $ticket->update(['status'=>'closed','closed_at'=>now()]);
    }

    public function addComment(int $id): void
    {
        if (empty(trim($this->newComment))) return;
        TicketComment::create(['ticket_id'=>$id,'user_id'=>auth()->id(),'content'=>trim($this->newComment)]);
        $this->newComment = '';
    }

    public function edit(int $id): void
    {
        $t = Ticket::findOrFail($id);
        $this->editingId=$id; $this->formTitle=$t->title; $this->formDescription=$t->description??'';
        $this->formType=$t->type; $this->formPriority=$t->priority; $this->formSource=$t->source;
        $this->formProjectId=$t->project_id??''; $this->formRegionId=$t->region_id??''; $this->formAssetId=$t->asset_id??''; $this->formAssignedTo=$t->assigned_to??'';
        $this->showForm=true;
    }

    public function toggleView(int $id): void { $this->viewTicketId = $this->viewTicketId === $id ? null : $id; }
    public function delete(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        if ($ticket->created_by != auth()->id() && !auth()->user()->can('manage tickets')) return;
        $ticket->delete();
    }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formTitle','formDescription','formType','formPriority','formSource','formProjectId','formRegionId','formAssetId','formAssignedTo']); $this->formType='request'; $this->formPriority='medium'; $this->formSource='portal'; }

    public function render()
    {
        $tickets = Ticket::with(['project','asset','assignee','creator'])->latest()->paginate(15);
        $projects = Project::orderBy('title')->get(['id','title']);
        $regions = Region::orderBy('sort_order')->get();
        $assets = Asset::orderBy('name')->get(['id','name','asset_tag']);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        $viewTicket = $this->viewTicketId ? Ticket::with('comments.user')->find($this->viewTicketId) : null;
        $openCount = Ticket::whereIn('status',['open','in_progress'])->count();
        return view('livewire.itsm.tickets', compact('tickets','projects','assets','users', 'regions','viewTicket','openCount'))
            ->layout('layouts.app', ['title' => '工单管理']);
    }
}
