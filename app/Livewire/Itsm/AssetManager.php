<?php

namespace App\Livewire\Itsm;

use App\Models\Asset;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AssetManager extends Component
{
    use WithPagination;

    public bool $showForm = false; public ?int $editingId = null;
    public string $formAssetTag = '', $formName = '', $formType = 'other', $formBrand = '', $formModel = '', $formSerial = '', $formStatus = 'in_use', $formLocation = '', $formDept = '', $formNotes = '';
    public int|string $formAssignedTo = '';
    public string $formPurchaseDate = '', $formWarrantyExpiry = '';

    protected $rules = ['formName'=>'required|max:100','formAssetTag'=>'required|unique:assets,asset_tag'];

    public function save(): void
    {
        $rules = $this->editingId ? ['formName'=>'required','formAssetTag'=>'required|unique:assets,asset_tag,'.$this->editingId] : $this->rules;
        $this->validate($rules);
        $data = ['asset_tag'=>$this->formAssetTag,'name'=>$this->formName,'type'=>$this->formType,'brand'=>$this->formBrand?:null,'model'=>$this->formModel?:null,'serial_number'=>$this->formSerial?:null,'status'=>$this->formStatus,'assigned_to'=>$this->formAssignedTo?:null,'location'=>$this->formLocation?:null,'department'=>$this->formDept?:null,'notes'=>$this->formNotes?:null,'purchase_date'=>$this->formPurchaseDate?:null,'warranty_expiry'=>$this->formWarrantyExpiry?:null];
        if ($this->editingId) { Asset::findOrFail($this->editingId)->update($data); }
        else { Asset::create($data); }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $a = Asset::findOrFail($id);
        $this->editingId=$id; $this->formAssetTag=$a->asset_tag; $this->formName=$a->name; $this->formType=$a->type; $this->formBrand=$a->brand??''; $this->formModel=$a->model??''; $this->formSerial=$a->serial_number??''; $this->formStatus=$a->status; $this->formAssignedTo=$a->assigned_to??''; $this->formLocation=$a->location??''; $this->formDept=$a->department??''; $this->formNotes=$a->notes??''; $this->formPurchaseDate=$a->purchase_date?->format('Y-m-d')??''; $this->formWarrantyExpiry=$a->warranty_expiry?->format('Y-m-d')??'';
        $this->showForm=true;
    }

    public function delete(int $id): void { Asset::findOrFail($id)->delete(); }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formAssetTag','formName','formType','formBrand','formModel','formSerial','formStatus','formLocation','formDept','formNotes','formAssignedTo','formPurchaseDate','formWarrantyExpiry']); $this->formType='other'; $this->formStatus='in_use'; }

    public function render()
    {
        $assets = Asset::with('assignee')->latest()->paginate(20);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        return view('livewire.itsm.assets', compact('assets','users'))
            ->layout('layouts.app', ['title' => '资产管理']);
    }
}
