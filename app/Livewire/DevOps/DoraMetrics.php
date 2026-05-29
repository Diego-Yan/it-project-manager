<?php

namespace App\Livewire\DevOps;

use App\Models\Deployment;
use App\Models\Incident;
use App\Models\Release;
use Livewire\Component;

class DoraMetrics extends Component
{
    public string $period = '30d'; // 7d|30d|90d

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->can('view all projects');
        $projectIds = $isAdmin ? \App\Models\Project::pluck('id') : $user->assignedProjects()->pluck('project_id');

        $since = match($this->period) { '7d'=>now()->subDays(7), '90d'=>now()->subDays(90), default=>now()->subDays(30) };

        // DORA 1: 部署频率 (次/周)
        $deployments = Deployment::whereIn('environment_id',
            \App\Models\Environment::whereIn('project_id', $projectIds)->pluck('id')
        )->where('status','success')->where('created_at','>=',$since)->count();
        $weeks = max(1, (int) now()->diffInDays($since) / 7);
        $deployFreq = round($deployments / $weeks, 1);

        // DORA 2: 变更交付周期 (hours, from change approved to release deployed)
        $changes = \App\Models\ChangeRequest::whereIn('project_id', $projectIds)
            ->where('status', 'completed')
            ->whereNotNull('implemented_at')
            ->where('updated_at', '>=', $since)
            ->get();
        $leadTimes = $changes->map(fn($c) => $c->created_at->diffInHours($c->implemented_at))->filter(fn($h) => $h > 0);
        $avgLeadTime = $leadTimes->count() > 0 ? round($leadTimes->avg(), 1) : null;

        // DORA 3: MTTR (故障恢复时间, hours)
        $incidents = Incident::whereIn('project_id', $projectIds)
            ->where('status','resolved')
            ->whereNotNull('resolved_at')
            ->whereNotNull('started_at')
            ->where('created_at','>=',$since)->get();
        $mttrs = $incidents->map(fn($i) => $i->started_at->diffInMinutes($i->resolved_at))->filter(fn($m) => $m > 0);
        $mttr = $mttrs->count() > 0 ? round($mttrs->avg(), 0) : null;

        // DORA 4: 变更失败率 (回滚+失败 / 总发布)
        $totalReleases = Release::whereIn('project_id', $projectIds)->where('created_at','>=',$since)->count();
        $failedReleases = Release::whereIn('project_id', $projectIds)->whereIn('status',['failed','rolled_back'])->where('created_at','>=',$since)->count();
        $failureRate = $totalReleases > 0 ? round($failedReleases / $totalReleases * 100, 1) : 0;

        // 趋势数据（按月）
        $months = collect(range(0, 2))->map(function($i) use ($projectIds) {
            $start = now()->subMonths($i+1)->startOfMonth();
            $end = now()->subMonths($i)->startOfMonth();
            return [
                'month' => $start->format('n月'),
                'deploys' => Deployment::whereIn('environment_id', \App\Models\Environment::whereIn('project_id',$projectIds)->pluck('id'))->where('status','success')->whereBetween('created_at',[$start,$end])->count(),
                'incidents' => Incident::whereIn('project_id',$projectIds)->whereBetween('created_at',[$start,$end])->count(),
                'failures' => Release::whereIn('project_id',$projectIds)->whereIn('status',['failed','rolled_back'])->whereBetween('created_at',[$start,$end])->count(),
            ];
        })->reverse()->values();

        return view('livewire.devops.dora', compact('deployFreq','avgLeadTime','mttr','failureRate','totalReleases','failedReleases','months'))
            ->layout('layouts.app', ['title' => 'DORA 指标']);
    }
}
