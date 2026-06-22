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
        // [REVIEW-FIX] C4: 资产保存需要管理权限（路由 only 阻止页面访问，不阻止 Livewire action）
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', __('没有资产管理权限'));
            return;
        }
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

        // [REVIEW-FIX] C3: 兼容 SQLite（不支持 lockForUpdate），用驱动判断选择策略
        // MySQL: SELECT ... FOR UPDATE 行锁; SQLite: 事务 + 重试
        // [REVIEW-FIX-R4 #7 P2] 修复消耗品 asset_tag 编号生成在超过 9999 条时的字典序 bug：
        // 原代码 max('asset_tag') 对字符串做字典序比较，CON-9999 > CON-10000（字典序），
        // 导致第 10000 条编号回退到 CON-0001，与已有记录冲突或覆盖。
        // 修复：提取数字部分做整数比较，而非对整个字符串做 max()。
        if ($this->formCategory === 'consumable') {
            $data['asset_tag'] = DB::transaction(function () {
                $isSqlite = DB::getDriverName() === 'sqlite';
                $query = Asset::where('category', 'consumable')
                    ->where('asset_tag', 'LIKE', 'CON-%');
                if (!$isSqlite) {
                    $query->lockForUpdate();
                }
                // 提取 CON- 后的数字部分做整数比较，避免字典序问题
                $allTags = $query->pluck('asset_tag')->filter()->values();
                $maxNum = 0;
                foreach ($allTags as $tag) {
                    $num = (int) substr($tag, 4);
                    if ($num > $maxNum) $maxNum = $num;
                }
                $nextNum = $maxNum + 1;
                return 'CON-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
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
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', __('没有资产管理权限'));
            return;
        }
        if (empty(trim($this->catalogName))) return;
        ConsumableCatalog::create(['name'=>trim($this->catalogName), 'brand'=>$this->catalogBrand?:null, 'unit'=>$this->catalogUnit]);
        $this->catalogName = ''; $this->catalogBrand = ''; $this->catalogUnit = __('个');
    }

    public function deleteCatalogItem(int $id): void
    {
        // [REVIEW-FIX-R3 #6 P3] 同 delete()：权限拒绝时给出明确反馈
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', __('没有资产管理权限'));
            return;
        }
        ConsumableCatalog::findOrFail($id)->delete();
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', __('没有资产管理权限'));
            return;
        }
        $a = Asset::findOrFail($id);
        $this->editingId=$id; $this->formAssetTag=$a->asset_tag; $this->formName=$a->name; $this->formType=$a->type; $this->formCategory=$a->category??'fixed'; $this->formBrand=$a->brand??''; $this->formModel=$a->model??''; $this->formSerial=$a->serial_number??''; $this->formStatus=$a->status; $this->formAssignedTo=$a->assigned_to??''; $this->formLocation=$a->location??''; $this->formDept=$a->department??''; $this->formNotes=$a->notes??''; $this->formPurchaseDate=$a->purchase_date?->format('Y-m-d')??''; $this->formWarrantyExpiry=$a->warranty_expiry?->format('Y-m-d')??'';
        $this->formQuantity=$a->quantity??1;
        $this->showForm=true;
    }

    public function delete(int $id): void
    {
        // [REVIEW-FIX-R3 #6 P3] 修复权限拒绝时静默返回无反馈：
        // 原代码 if (!can) return; → 用户点击删除无任何反应，无法区分"删除成功"和"无权限被拒"。
        // 改为明确提示无权限，与 save()/edit() 等方法的错误反馈模式一致。
        if (!auth()->user()->can('manage assets')) {
            session()->flash('error', __('没有资产管理权限'));
            return;
        }
        Asset::findOrFail($id)->delete();
        session()->flash('success', __('资产已删除'));
    }
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formAssetTag','formName','formType','formBrand','formModel','formSerial','formStatus','formLocation','formDept','formNotes','formAssignedTo','formPurchaseDate','formWarrantyExpiry','formQuantity']); $this->formType='other'; $this->formCategory='fixed'; $this->formStatus='in_use'; $this->formQuantity=1; }

    public function render()
    {
        $assets = Asset::with('assignee')->latest()->paginate(20);
        $users = User::where('is_active',true)->orderBy('name')->get(['id','name']);
        $catalog = ConsumableCatalog::where('is_active',true)->orderBy('name')->get();
        return view('livewire.itsm.assets', compact('assets','users','catalog'))
            ->layout('layouts.app', ['title' => __('资产管理')]);
    }
}
