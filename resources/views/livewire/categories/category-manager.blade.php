<div class="space-y-6">

    {{-- 页头操作栏 --}}
    <div class="flex items-center justify-between">
        <div></div>
        @can('create categories')
        <button wire:click="$set('showForm', true)"
            class="flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl transition-all hover:shadow-lg hover:shadow-sky-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            {{ __('新建分类') }}
        </button>
        @endcan
    </div>

    {{-- 新建 / 编辑表单 --}}
    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">
            {{ $editingId ? __('编辑分类') : __('新建分类') }}
        </h3>
        <div class="space-y-4">

            {{-- 所属类型 --}}
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">{{ __('所属项目类型') }}</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="type" value="ops"
                            class="w-4 h-4 text-sky-600 border-zinc-300 focus:ring-sky-500">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('运维项目') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="type" value="dev"
                            class="w-4 h-4 text-violet-600 border-zinc-300 focus:ring-violet-500">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('开发项目') }}</span>
                    </label>
                </div>
                @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 分类名称 --}}
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">{{ __('分类名称') }}</label>
                <input type="text" wire:model="name" placeholder="{{ __('如：网络运维 / HR系统') }}"
                    class="w-full px-4 py-2.5 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- 颜色 --}}
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">{{ __('颜色标签') }}</label>
                @php
                $colorPalette = [
                    'red'    => ['label' => __('赤'), 'hex' => '#ef4444'],
                    'orange' => ['label' => __('橙'), 'hex' => '#f97316'],
                    'yellow' => ['label' => __('黄'), 'hex' => '#eab308'],
                    'green'  => ['label' => __('绿'), 'hex' => '#22c55e'],
                    'cyan'   => ['label' => __('青'), 'hex' => '#06b6d4'],
                    'blue'   => ['label' => __('蓝'), 'hex' => '#3b82f6'],
                    'purple' => ['label' => __('紫'), 'hex' => '#a855f7'],
                ];
                @endphp
                <div class="flex gap-2 flex-wrap">
                    @foreach($colorPalette as $c => $info)
                    <button type="button" wire:click="$set('color', '{{ $c }}')"
                        style="background-color: {{ $info['hex'] }}; {{ $color === $c ? 'outline: 3px solid white; outline-offset: 2px; transform: scale(1.15);' : '' }}"
                        class="w-8 h-8 rounded-lg border-2 transition-all {{ $color === $c ? 'border-white shadow-lg' : 'border-transparent' }}"
                        title="{{ $info['label'] }}"></button>
                    @endforeach
                </div>
            </div>

            {{-- 描述 --}}
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">{{ __('描述（可选）') }}</label>
                <input type="text" wire:model="description" placeholder="{{ __('简短描述此分类') }}"
                    class="w-full px-4 py-2.5 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500">
            </div>

            <div class="flex gap-2 justify-end">
                <button wire:click="$set('showForm', false); $set('editingId', null)"
                    class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">{{ __('取消') }}</button>
                <button wire:click="save"
                    class="px-5 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-colors">{{ __('保存') }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- 运维项目分类 --}}
    <div>
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('运维项目') }}</h2>
            <span class="text-xs text-zinc-400">{{ __(':count 个分类', ['count' => $opsCategories->count()]) }}</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($opsCategories as $cat)
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        @php
                        $dotColors = ['red'=>'#ef4444','orange'=>'#f97316','yellow'=>'#eab308','green'=>'#22c55e','cyan'=>'#06b6d4','blue'=>'#3b82f6','purple'=>'#a855f7'];
                        $dotHex = $dotColors[$cat->color] ?? '#94a3b8';
                        @endphp
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $dotHex }};"></div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $cat->name }}</h3>
                    </div>
                    <div class="flex items-center gap-1">
                        @can('edit categories')
                        <button wire:click="edit({{ $cat->id }})" class="p-1.5 text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        </button>
                        @endcan
                        @can('delete categories')
                        <button wire:click="delete({{ $cat->id }})"
                            wire:confirm="{{ __('确定删除「:name」分类？', ['name' => $cat->name]) }}"
                            class="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                        @endcan
                    </div>
                </div>
                @if($cat->description)
                <p class="text-xs text-zinc-500 mb-3">{{ $cat->description }}</p>
                @endif
                <span class="text-xs text-zinc-400">{{ __(':count 个项目', ['count' => $cat->projects_count]) }}</span>
            </div>
            @empty
            <div class="col-span-3 text-center py-8 text-zinc-400 text-sm">{{ __('暂无运维项目分类') }}</div>
            @endforelse
        </div>
    </div>

    {{-- 开发项目分类 --}}
    <div>
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ __('开发项目') }}</h2>
            <span class="text-xs text-zinc-400">{{ __(':count 个分类', ['count' => $devCategories->count()]) }}</span>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($devCategories as $cat)
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        @php
                        $dotColors = ['red'=>'#ef4444','orange'=>'#f97316','yellow'=>'#eab308','green'=>'#22c55e','cyan'=>'#06b6d4','blue'=>'#3b82f6','purple'=>'#a855f7'];
                        $dotHex = $dotColors[$cat->color] ?? '#94a3b8';
                        @endphp
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $dotHex }};"></div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $cat->name }}</h3>
                    </div>
                    <div class="flex items-center gap-1">
                        @can('edit categories')
                        <button wire:click="edit({{ $cat->id }})" class="p-1.5 text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        </button>
                        @endcan
                        @can('delete categories')
                        <button wire:click="delete({{ $cat->id }})"
                            wire:confirm="{{ __('确定删除「:name」分类？', ['name' => $cat->name]) }}"
                            class="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                        @endcan
                    </div>
                </div>
                @if($cat->description)
                <p class="text-xs text-zinc-500 mb-3">{{ $cat->description }}</p>
                @endif
                <span class="text-xs text-zinc-400">{{ __(':count 个项目', ['count' => $cat->projects_count]) }}</span>
            </div>
            @empty
            <div class="col-span-3 text-center py-8 text-zinc-400 text-sm">{{ __('暂无开发项目分类') }}</div>
            @endforelse
        </div>
    </div>

</div>
