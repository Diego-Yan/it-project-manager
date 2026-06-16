<?php

namespace App\Livewire;

use App\Models\Asset;
use Livewire\Component;
use Livewire\WithPagination;

class MyAssets extends Component
{
    use WithPagination;

    public function render()
    {
        $assets = Asset::where('assigned_to', auth()->id())
            ->latest()
            ->paginate(15);

        // [REVIEW-FIX] R3.6: 4次独立 COUNT → 1次 GROUP BY
        $uid = auth()->id();
        $countsRaw = Asset::where('assigned_to', $uid)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use,
                SUM(CASE WHEN status = 'repair' THEN 1 ELSE 0 END) as repair,
                SUM(CASE WHEN warranty_expiry IS NOT NULL AND warranty_expiry <= ? AND status != 'retired' THEN 1 ELSE 0 END) as warranty_soon
            ", [now()->addDays(30)])->first();
        $counts = [
            'total'         => (int) ($countsRaw->total ?? 0),
            'in_use'        => (int) ($countsRaw->in_use ?? 0),
            'repair'        => (int) ($countsRaw->repair ?? 0),
            'warranty_soon' => (int) ($countsRaw->warranty_soon ?? 0),
        ];

        return view('livewire.my-assets', compact('assets', 'counts'))
            ->layout('layouts.app', ['title' => '我的资产']);
    }
}
