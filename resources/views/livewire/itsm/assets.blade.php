<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('资产管理') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('IT 设备台账：电脑、打印机、网络设备、软件许可') }}</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">{{ __('+ 添加资产') }}</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? __('编辑资产') : __('添加资产') }}</h3>

        {{-- 分类首位置 --}}
        <div class="flex items-center gap-3 mb-4">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('资产分类') }}</label>
            <select wire:model.live="formCategory" class="px-4 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                <option value="fixed">{{ __('固定资产') }}</option><option value="non_fixed">{{ __('非固定资产') }}</option><option value="consumable">{{ __('损耗品') }}</option>
            </select>
        </div>

        <div class="grid sm:grid-cols-3 gap-3">
            {{-- 固定资产/非固定资产：编号 --}}
            @if($formCategory !== 'consumable')
            <input wire:model="formAssetTag" placeholder="{{ $formCategory === 'fixed' ? __('固定资产编号') : __('管制资产编号') }} *" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            @endif

            {{-- 损耗品：下拉选择 --}}
            @if($formCategory === 'consumable')
            <select wire:model="formName" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">{{ __('选择损耗品') }} *</option>
                @foreach($catalog as $item)
                <option value="{{ $item->name }}">{{ $item->name }} @if($item->brand) ({{ $item->brand }}) @endif</option>
                @endforeach
            </select>
            <input type="number" wire:model="formQuantity" min="1" placeholder="{{ __('库存数量') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            @else
            {{-- 固定资产/非固定资产：设备名称 --}}
            <input wire:model="formName" placeholder="{{ __('设备名称') }} *" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">

            {{-- 设备类型 --}}
            <select wire:model="formType" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="laptop">{{ __('💻 笔记本') }}</option><option value="desktop">{{ __('🖥️ 台式机') }}</option><option value="printer">{{ __('🖨️ 打印机') }}</option><option value="switch">{{ __('🌐 交换机') }}</option><option value="server">{{ __('🗄️ 服务器') }}</option><option value="monitor">{{ __('🖥️ 显示器') }}</option><option value="software">{{ __('💿 软件') }}</option><option value="other">{{ __('📦 其他') }}</option></select>

            {{-- 品牌 + 型号 --}}
            <input wire:model="formBrand" placeholder="{{ __('品牌') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input wire:model="formModel" placeholder="{{ __('型号') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">

            {{-- 固定资产才有序列号 --}}
            @if($formCategory === 'fixed')
            <input wire:model="formSerial" placeholder="{{ __('序列号') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            @endif

            <select wire:model="formStatus" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="in_use">{{ __('使用中') }}</option><option value="available">{{ __('空闲') }}</option><option value="repair">{{ __('维修中') }}</option><option value="retired">{{ __('已报废') }}</option></select>
            <select wire:model="formAssignedTo" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">{{ __('使用人') }}</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <input wire:model="formLocation" placeholder="{{ __('位置: 3楼A区') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input type="date" wire:model="formPurchaseDate" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" title="{{ __('购买日期') }}">
            <input type="date" wire:model="formWarrantyExpiry" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" title="{{ __('保修到期') }}">
            @endif
        </div>

        <textarea wire:model="formNotes" rows="2" placeholder="{{ __('备注') }}" class="w-full mt-3 px-3 min-h-[5rem] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        @error('formAssetTag')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">{{ __('取消') }}</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存') }}</button></div>
    </div>
    @endif

    {{-- 损耗品目录管理 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('损耗品目录') }}</h3>
            <button wire:click="$toggle('showCatalog')" class="text-xs text-sky-600 hover:underline">{{ $showCatalog ? __('收起') : __('管理') }}</button>
        </div>
        @if($showCatalog)
        <div class="flex gap-2 mb-3">
            <input wire:model="catalogName" wire:keydown.enter="addCatalogItem" placeholder="{{ __('名称') }}" class="flex-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input wire:model="catalogBrand" placeholder="{{ __('品牌') }}" class="w-28 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="catalogUnit" class="px-2 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="个">{{ __('个') }}</option><option value="箱">{{ __('箱') }}</option><option value="包">{{ __('包') }}</option><option value="支">{{ __('支') }}</option><option value="卷">{{ __('卷') }}</option><option value="套">{{ __('套') }}</option></select>
            <button wire:click="addCatalogItem" class="px-3 py-2 text-xs font-medium bg-sky-600 text-white rounded-xl">{{ __('添加') }}</button>
        </div>
        <div class="flex gap-2 flex-wrap">
            @foreach($catalog as $item)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                {{ $item->name }} @if($item->brand)({{ $item->brand }})@endif
                <button wire:click="deleteCatalogItem({{ $item->id }})" class="text-zinc-400 hover:text-red-500 ml-1">&times;</button>
            </span>
            @endforeach
        </div>
        @endif
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($assets->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无资产') }}</div>
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
                        @if($a->category === 'consumable')<span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('库存: ') }}{{ $a->quantity }}</span>@endif
                    </div>
                    <div class="text-xs text-zinc-500 mt-0.5">
                        {{ $a->brand ?: '' }} {{ $a->model ?: '' }} {{ $a->serial_number ? '· SN:'.$a->serial_number : '' }}
                        @if($a->assignee)<span>· {{ $a->assignee->name }}</span>@endif
                        @if($a->location)<span>· {{ $a->location }}</span>@endif
                        @if($a->warranty_expiry)<span class="{{ $a->warranty_expiry->isPast() ? 'text-red-500' : 'text-zinc-400' }}">{{ __('· 保修至 ') }}{{ $a->warranty_expiry->format('Y-m-d') }}</span>@endif
                    </div>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button wire:click="edit({{ $a->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                    <button wire:click="delete({{ $a->id }})" wire:confirm="{{ __('删除？') }}" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($assets->hasPages())<div class="flex justify-center">{{ $assets->links() }}</div>@endif
</div>
