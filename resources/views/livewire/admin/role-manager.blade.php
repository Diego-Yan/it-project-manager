<div class="p-6 space-y-6">
    {{-- 页面标题 --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('角色管理') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('管理系统角色及其权限划分') }}</p>
        </div>
        <button wire:click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('新建角色') }}
        </button>
    </div>

    {{-- Flash 消息 --}}
    @if(session()->has('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session()->has('error'))
    <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- 角色卡片列表 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($roles as $role)
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $role->name }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __(':count 名用户', ['count' => $role->users_count]) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="openEditModal({{ $role->id }})"
                        class="p-1.5 text-zinc-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    @if(!in_array($role->name, ['超级管理员']))
                    <button wire:click="confirmDelete({{ $role->id }})"
                        class="p-1.5 text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    @endif
                </div>
            </div>

            @if($role->description)
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">{{ $role->description }}</p>
            @endif

            {{-- 权限标签 --}}
            <div class="flex flex-wrap gap-1.5">
                @foreach($role->permissions as $perm)
                <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">
                    {{ $permLabels[$perm->name] ?? $perm->name }}
                </span>
                @endforeach
                @if($role->permissions->isEmpty())
                <span class="text-xs text-zinc-400 dark:text-zinc-500 italic">{{ __('暂无权限') }}</span>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 py-12 text-center text-zinc-400">
            <p>{{ __('暂无角色') }}</p>
        </div>
        @endforelse
    </div>

{{-- ══ 新建/编辑角色 Modal ══ --}}
@if($showRoleModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $isEditing ? __('编辑角色') : __('新建角色') }}
            </h2>
            <button wire:click="$set('showRoleModal', false)" class="p-2 text-zinc-400 hover:text-zinc-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-5">
            {{-- 角色名称 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('角色名称') }} <span class="text-red-500">*</span></label>
                <input wire:model="formName" type="text" placeholder="{{ __('如：项目经理、运维工程师...') }}"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                @error('formName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 描述 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('角色描述') }}</label>
                <input wire:model="formDescription" type="text" placeholder="{{ __('简短描述该角色的职责...') }}"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            {{-- 权限分组 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">{{ __('权限配置') }}</label>
                <div class="space-y-4">
                    @foreach($permGroups as $groupName => $groupPerms)
                    @php
                        $groupSelected = count(array_intersect($groupPerms, $selectedPerms));
                        $groupTotal = count($groupPerms);
                        $allGroupSelected = $groupSelected === $groupTotal;
                    @endphp
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                        {{-- 分组标题行（全选按钮） --}}
                        <button wire:click="toggleGroup('{{ $groupName }}')" type="button"
                            class="w-full flex items-center justify-between px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors text-left">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $groupName }}</span>
                                <span class="text-xs text-zinc-400">{{ $groupSelected }}/{{ $groupTotal }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-sky-600 dark:text-sky-400">{{ $allGroupSelected ? __('取消全选') : __('全选') }}</span>
                                <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-colors
                                    {{ $allGroupSelected ? 'bg-sky-600 border-sky-600' : ($groupSelected > 0 ? 'bg-sky-200 border-sky-400' : 'border-zinc-300 dark:border-zinc-600') }}">
                                    @if($allGroupSelected)
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @elseif($groupSelected > 0)
                                    <div class="w-2 h-0.5 bg-sky-600"></div>
                                    @endif
                                </div>
                            </div>
                        </button>
                        {{-- 权限列表 --}}
                        <div class="grid grid-cols-2 gap-0 divide-y divide-zinc-100 dark:divide-zinc-700/50 border-t border-zinc-200 dark:border-zinc-700">
                            @foreach($groupPerms as $perm)
                            <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 cursor-pointer transition-colors">
                                <input type="checkbox" wire:model="selectedPerms" value="{{ $perm }}"
                                    class="w-4 h-4 rounded text-sky-600 border-zinc-300 dark:border-zinc-600 focus:ring-sky-500">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $permLabels[$perm] ?? $perm }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3 sticky bottom-0 bg-white dark:bg-zinc-800">
            <button wire:click="$set('showRoleModal', false)"
                class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                {{ __('取消') }}
            </button>
            <button wire:click="saveRole"
                class="px-4 py-2 text-sm font-medium bg-sky-600 hover:bg-sky-700 text-white rounded-xl transition-colors">
                {{ $isEditing ? __('保存修改') : __('创建角色') }}
            </button>
        </div>
    </div>
</div>
@endif

{{-- ══ 删除确认 ══ --}}
@if($showDeleteModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('确认删除角色') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">{{ __('删除后该角色的用户将失去对应权限。') }}</p>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button wire:click="$set('showDeleteModal', false)"
                class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                {{ __('取消') }}
            </button>
            <button wire:click="deleteRole"
                class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">
                {{ __('确认删除') }}
            </button>
        </div>
    </div>
</div>
@endif

</div>
