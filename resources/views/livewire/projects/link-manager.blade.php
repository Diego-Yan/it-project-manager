<div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('项目关联') }}</h3>
        <button wire:click="$toggle('showLinkForm')"
            class="text-xs text-sky-600 dark:text-sky-400 hover:underline">
            {{ $showLinkForm ? __('取消') : __('+ 添加关联') }}
        </button>
    </div>

    {{-- 阻断警告 --}}
    @php $blockers = $project->blockingProjects; @endphp
    @if($blockers->isNotEmpty())
    <div class="mb-4 p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl">
        <p class="text-sm font-medium text-red-700 dark:text-red-400 mb-1">{{ __('等待依赖项目完成') }}</p>
        @foreach($blockers as $bp)
        <a href="{{ route('projects.show', $bp) }}" class="block text-xs text-red-600 dark:text-red-400 hover:underline">
            {{ $bp->title }} ({{ $bp->progressLabel }})
        </a>
        @endforeach
    </div>
    @endif

    {{-- 添加关联表单 --}}
    @if($showLinkForm)
    <div class="mb-4 p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">{{ __('关系类型') }}</label>
                <select wire:model="linkType"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300">
                    <option value="relates_to">{{ __('↔ 关联引用') }}</option>
                    <option value="blocks">{{ __('→ 阻断依赖（A 完成后 B 才能开始）') }}</option>
                    <option value="parent">{{ __('← 设置父项目') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                    {{ __('目标项目') }}
                </label>
                <input type="text" wire:model.live.debounce.200ms="searchLink" placeholder="{{ __('搜索项目名称...') }}"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500">
                @if($searchResults)
                <div class="mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden max-h-36 overflow-y-auto">
                    @foreach($searchResults as $r)
                    <button type="button" wire:click="selectTarget({{ $r['id'] }}, '{{ $r['title'] }}')"
                        class="w-full text-left px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                        {{ $r['title'] }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="flex gap-2 justify-end">
                <button wire:click="$set('showLinkForm', false)"
                    class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">{{ __('取消') }}</button>
                <button wire:click="addLink"
                    class="px-4 py-1.5 text-xs font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-lg transition-colors">{{ __('创建关联') }}</button>
            </div>
        </div>
    </div>
    @endif

    @if(session('link_success'))
    <div class="mb-3 text-xs text-green-600 dark:text-green-400">{{ session('link_success') }}</div>
    @endif
    @if(session('link_error'))
    <div class="mb-3 text-xs text-red-500">{{ session('link_error') }}</div>
    @endif

    {{-- 关联列表 --}}
    @if($outgoing->isEmpty() && $incoming->isEmpty())
    <p class="text-xs text-zinc-400">{{ __('暂无项目关联') }}</p>
    @else
    <div class="space-y-2">
        @foreach($outgoing as $link)
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0 w-20">
                {{ $link->link_type === 'parent' ? __('← 父项目') : $link->linkTypeDirectionLabel }}
            </span>
            <a href="{{ route('projects.show', $link->target) }}" class="flex-1 min-w-0 text-sm text-sky-600 dark:text-sky-400 hover:underline truncate">
                {{ $link->target->title }}
            </a>
            <button wire:click="removeLink({{ $link->id }})"
                wire:confirm="{{ __('确定解除此关联？') }}"
                class="text-xs text-zinc-400 hover:text-red-500 shrink-0">{{ __('解除') }}</button>
        </div>
        @endforeach

        @foreach($incoming as $link)
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 shrink-0 w-20">
                {{ $link->link_type === 'parent' ? __('→ 子项目') : ($link->link_type === 'blocks' ? __('← 被阻断') : __('↔ 被关联')) }}
            </span>
            <a href="{{ route('projects.show', $link->project) }}" class="flex-1 min-w-0 text-sm text-sky-600 dark:text-sky-400 hover:underline truncate">
                {{ $link->project->title }}
            </a>
            <button wire:click="removeLink({{ $link->id }})"
                wire:confirm="{{ __('确定解除此关联？') }}"
                class="text-xs text-zinc-400 hover:text-red-500 shrink-0">{{ __('解除') }}</button>
        </div>
        @endforeach
    </div>
    @endif
</div>
