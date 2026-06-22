<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('地区管理') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('管理工单和项目归属地区') }}</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">{{ __('+ 新增地区') }}</button>
    </div>

    @if(session('success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">{{ session('error') }}</div>@endif

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? __('编辑地区') : __('新增地区') }}</h3>
        <div class="flex gap-3 items-end">
            <div class="flex-1"><label class="text-xs font-medium text-zinc-500">{{ __('地区名称') }}</label><input wire:model="formName" wire:keydown.enter="save" placeholder="{{ __('输入地区名称') }}" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存') }}</button>
            <button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">{{ __('取消') }}</button>
        </div>
        @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($regions->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无地区') }}</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($regions as $r)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <span class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950/40 flex items-center justify-center text-sky-600 text-sm font-bold">{{ mb_substr($r->name, 0, 1) }}</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-white flex-1">{{ $r->name }}</span>
                <button wire:click="edit({{ $r->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                <button wire:click="delete({{ $r->id }})" wire:confirm="{{ __('确定删除「:name」？', ['name' => $r->name]) }}" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/svg></button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
