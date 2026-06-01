<?php

namespace App\Livewire\Itsm;

use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use App\Models\ConsumableCatalog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AssetManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formAssetTag = '', $formName = '', $formType = 'other', $formCategory = 'fixed', $formBrand = '', $formModel = '', $formSerial = '', $formStatus = 'in_use', $formLocation = '', $formDept = '', $formNotes = '';
    public int|string $formAssignedTo = '';
    public int $formQuantity = 1;
    public string $formPurchaseDate = '', $formWarrantyExpiry = '';

    // 损耗品目录管理
    public bool $showCatalog = false;
    public string $catalogName = '', $catalogBrand = '', $catalogUnit = '个';

    protected $rules = ['formName'=>'required|max:100'];

    public function save(): void
    {
        $rules = ['formName'=>'required|max:100'];
        if ($this->formCategory !== 'consumable') {
            $tagRule = $this->editingId ? 'required|unique:assets,asset_tag,'.$this->editingId : 'required|unique:assets,asset_tag';
            $rules['formAssetTag'] = $tagRule;
        }
        $this->validate($rules);

        $data = [
            'name' => $this->formName,
            'type' => $this->formCategory === 'consumable' ? 'other' : $this->formType,
            'category' => $this->formCategory,
            'brand' => $this->formBrand ?: null,
            'model' => $this->formCategory !== 'consumable' ? ($this->formModel ?: null) : null,
            'serial_number' => $this->formCategory === 'fixed' ? ($this->formSerial ?: null) : null,
            'status' => $this->formCategory === 'consumable' ? 'in_use' : $this->formStatus,
            'quantity' => $this->formCategory === 'consumable' ? max(1, (int)$this->formQuantity) : 1,
            'assigned_to' => $this->formCategory !== 'consumable' ? ($this->formAssignedTo ?: null) : null,
            'location' => $this->formCategory !== 'consumable' ? ($this->formLocation ?: null) : null,
            'department' => $this->formDept ?: null,
            'notes' => $this->formNotes ?: null,
            'purchase_date' => $this->formCategory !== 'consumable' ? ($this->formPurchaseDate ?: null) : null,
            'warranty_expiry' => $this->formCategory !== 'consumable' ? ($this->formWarrantyExpiry ?: null) : null,
        ];

        // [FIX] #7: 使用事务 + MAX 聚合避免并发竞态（原代码: count()+1，并发会重复）
        if ($this->formCategory === 'consumable') {
            $data['asset_tag'] = DB::transaction(function () {
                $maxTag = Asset::where('category','consumable')
                    ->where('asset_tag', 'LIKE', 'CON-%')
                    ->lockForUpdate()->max('asset_tag');
                $nextNum = $maxTag ? (int)substr($maxTag, 4) + 1 : 1;
                return 'CON-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            });
        } else {
            $data['asset_tag'] = $this->formAssetTag;
        }

        if ($this->editingId) { Asset::findOrFail($this->editingId)->update($data); }
        else { Asset::create($data); }
        $this->resetForm();
    }

    // ── 损耗品目录管理 ──────────────────────────────────

    public function addCatalogItem(): void
    {
        if (empty(trim($this->catalogName))) return;
        ConsumableCatalog::create(['name'=>trim($this->catalogName), 'brand'=>$this->catalogBrand?:null, 'unit'=>$this->catalogUnit]);
        $this->catalogName = ''; $this->catalogBrand = ''; $this->catalogUnit = '个';
    }

    public function deleteCatalogItem(int $id): void
    {
        if (auth()->user()->can('manage assets')) ConsumableCatalog::findOrFail($id)->delete();
    }

    public function edit(int $id): void
    {
        $a = Asset::findOrFail($id);
        $this->editingId=$id; $this->formAssetTag=$a->asset_tag; $this->formName=$a->name; $this->formType=$a->type; $this->formCategory=$a->category??'fixed'; $this->formBrand=$a->brand??''; $this->formModel=$a->model??''; $this->formSerial=$a->serial_number??''; $this->formStatus=$a->status; $this->formAssignedTo=$a->assigned_to??''; $this->formLocation=$a->location??''; $this->formDept=$a->department??''; $this->formNotes=$a->notes??''; $this->formPurchaseDate=$a->purchase_date?->format('Y-m-d')??''; $this->formWarrantyExpiry=$a->warranty_expiry?->format('Y-m-d')??'';
        $this->formQuantity=$a->quantity??1;
        $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("manage assets")) return; Asset::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formAssetTag','formName','formType','formBrand','formModel','formSerial','formStatus','formLocation','formDept','formNotes','formAssignedTo','formPurchaseDate','formWarrantyExpiry','formQuantity']); $this->formType='other'; $this->formCategory='fixed'; $this->formStatus='in_use'; $this->formQuantity=1; }

    public function render()
    {
        $assets = Asset::with('assignee')->latest()->paginate(20);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        $catalog = ConsumableCatalog::where('is_active',true)->orderBy('name')->get();
        return view('livewire.itsm.assets', compact('assets','users','catalog'))
            ->layout('layouts.app', ['title' => '资产管理']);
    }
}
