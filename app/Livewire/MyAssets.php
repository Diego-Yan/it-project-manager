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

        $counts = [
            'total'      => Asset::where('assigned_to', auth()->id())->count(),
            'in_use'     => Asset::where('assigned_to', auth()->id())->where('status', 'in_use')->count(),
            'repair'     => Asset::where('assigned_to', auth()->id())->where('status', 'repair')->count(),
            'warranty_soon' => Asset::where('assigned_to', auth()->id())
                ->whereNotNull('warranty_expiry')
                ->where('warranty_expiry', '<=', now()->addDays(30))
                ->where('status', '!=', 'retired')
                ->count(),
        ];

        return view('livewire.my-assets', compact('assets', 'counts'))
            ->layout('layouts.app', ['title' => '我的资产']);
    }
}
