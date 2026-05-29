<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">资产管理</h1><p class="text-sm text-zinc-500 mt-1">IT 设备台账：电脑、打印机、网络设备、软件许可</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 添加资产</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑资产' : '添加资产' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formAssetTag" placeholder="资产编号: IT-2024-0001*" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input wire:model="formName" placeholder="设备名称*" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formType" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="laptop">💻 笔记本</option><option value="desktop">🖥️ 台式机</option><option value="printer">🖨️ 打印机</option><option value="switch">🌐 交换机</option><option value="server">🗄️ 服务器</option><option value="monitor">🖥️ 显示器</option><option value="software">💿 软件</option><option value="other">📦 其他</option></select>
            <select wire:model.live="formCategory" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="fixed">固定资产</option><option value="non_fixed">非固定资产</option><option value="consumable">损耗品</option></select>
            <input wire:model="formBrand" placeholder="品牌" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <div>
                <label class="text-xs text-zinc-500">{{ $formCategory === 'consumable' ? '库存数量' : '型号' }}</label>
                @if($formCategory === 'consumable')
                <input type="number" wire:model="formQuantity" min="1" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                @else
                <input wire:model="formModel" placeholder="型号" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                @endif
            </div>
            <input wire:model="formSerial" placeholder="序列号" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formStatus" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="in_use">使用中</option><option value="available">空闲</option><option value="repair">维修中</option><option value="retired">已报废</option></select>
            <select wire:model="formAssignedTo" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">使用人</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <input wire:model="formLocation" placeholder="位置: 3楼A区" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input type="date" wire:model="formPurchaseDate" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" title="购买日期">
            <input type="date" wire:model="formWarrantyExpiry" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" title="保修到期">
        </div>
        <textarea wire:model="formNotes" rows="2" placeholder="备注" class="w-full mt-3 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        @error('formAssetTag')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button></div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($assets->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无资产</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($assets as $a)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <span class="text-lg shrink-0">{{ $a->typeIcon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $a->name }}</span>
                        <span class="text-xs font-mono text-zinc-500">{{ $a->asset_tag }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $a->categoryColor }}-100 dark:bg-{{ $a->categoryColor }}-950/40 text-{{ $a->categoryColor }}-700 dark:text-{{ $a->categoryColor }}-400">{{ $a->categoryLabel }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $a->statusColor }}-100 dark:bg-{{ $a->statusColor }}-950/40 text-{{ $a->statusColor }}-700 dark:text-{{ $a->statusColor }}-400">{{ $a->statusLabel }}</span>
                        @if($a->category === 'consumable')<span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">库存: {{ $a->quantity }}</span>@endif
                    </div>
                    <div class="text-xs text-zinc-500 mt-0.5">
                        {{ $a->brand ?: '' }} {{ $a->model ?: '' }} {{ $a->serial_number ? '· SN:'.$a->serial_number : '' }}
                        @if($a->assignee)<span>· {{ $a->assignee->name }}</span>@endif
                        @if($a->location)<span>· {{ $a->location }}</span>@endif
                        @if($a->warranty_expiry)<span class="{{ $a->warranty_expiry->isPast() ? 'text-red-500' : 'text-zinc-400' }}">· 保修至 {{ $a->warranty_expiry->format('Y-m-d') }}</span>@endif
                    </div>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button wire:click="edit({{ $a->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                    <button wire:click="delete({{ $a->id }})" wire:confirm="删除？" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($assets->hasPages())<div class="flex justify-center">{{ $assets->links() }}</div>@endif
</div>
