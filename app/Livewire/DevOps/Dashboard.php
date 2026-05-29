<?php

namespace App\Livewire\DevOps;

use App\Models\ChangeRequest;
use App\Models\Incident;
use App\Models\Project;
use App\Models\Release;
use App\Models\Service;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->can('view all projects');

        $projectQuery = $isAdmin ? Project::query() : Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))->orWhere('created_by', $user->id);
        $projectIds = (clone $projectQuery)->pluck('id');

        $stats = [
            'services'        => Service::whereIn('project_id', $projectIds)->count(),
            'services_down'   => Service::whereIn('project_id', $projectIds)->where('status', 'down')->count(),
            'active_changes'  => ChangeRequest::whereIn('project_id', $projectIds)->whereIn('status', ['pending_approval','approved','in_progress'])->count(),
            'open_incidents'  => Incident::whereIn('project_id', $projectIds)->whereIn('status', ['open','investigating'])->count(),
            'recent_releases' => Release::whereIn('project_id', $projectIds)->where('status', 'success')->latest()->limit(5)->count(),
        ];

        $services = Service::whereIn('project_id', $projectIds)->with('project')->latest()->limit(10)->get();
        $openIncidents = Incident::whereIn('project_id', $projectIds)->whereIn('status', ['open','investigating'])->with(['project','assignee'])->latest()->limit(5)->get();
        $pendingChanges = ChangeRequest::whereIn('project_id', $projectIds)->where('status', 'pending_approval')->with(['project','requester'])->latest()->limit(5)->get();
        $recentReleases = Release::whereIn('project_id', $projectIds)->with(['project','deployer'])->latest()->limit(5)->get();

        return view('livewire.devops.dashboard', compact(
            'stats','services','openIncidents','pendingChanges','recentReleases'
        ))->layout('layouts.app', ['title' => 'DevOps 概览']);
    }
}
