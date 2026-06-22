<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Webhook 通知') }}</h1>
            <p class="text-sm text-zinc-500 mt-1">{{ __('配置企业微信/钉钉/自定义 Webhook，项目变动时自动推送通知') }}</p>
        </div>
        <button wire:click="$set('showForm', true)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('添加 Webhook') }}
        </button>
    </div>

    @if(session()->has('success'))
    <div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>
    @endif

    {{-- 表单 --}}
    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">{{ $editingId ? __('编辑 Webhook') : __('新建 Webhook') }}</h3>
        <div class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('名称') }} <span class="text-red-500">*</span></label>
                    <input wire:model="formName" placeholder="{{ __('如：项目群机器人') }}"
                        class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500">
                    @error('formName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('类型') }}</label>
                    <select wire:model="formType"
                        class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500">
                        <option value="wechat">{{ __('企业微信') }}</option>
                        <option value="dingtalk">{{ __('钉钉') }}</option>
                        <option value="custom">{{ __('自定义 JSON') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Webhook URL <span class="text-red-500">*</span></label>
                <input wire:model="formUrl" placeholder="https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=..."
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500">
                @error('formUrl') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 mb-1">{{ __('绑定项目（空=全局）') }}</label>
                    <select wire:model="formProjectId"
                        class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500">
                        <option value="">— {{ __('全局') }} —</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="formIsActive"
                            class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('启用') }}</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-2">{{ __('触发事件（不选=全部）') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($availableEvents as $key => $label)
                    <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs cursor-pointer transition-colors
                        {{ in_array($key, $selectedEvents) ? 'border-sky-400 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        <input type="checkbox" wire:model="selectedEvents" value="{{ $key }}" class="sr-only">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button wire:click="resetForm"
                    class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl">{{ __('取消') }}</button>
                <button wire:click="save"
                    class="px-4 py-2 text-sm font-medium bg-sky-600 hover:bg-sky-500 text-white rounded-xl">{{ __('保存') }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Webhook 列表 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($webhooks->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无 Webhook 配置') }}</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($webhooks as $webhook)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $webhook->name }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                            {{ $webhook->type === 'wechat' ? 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400' : '' }}
                            {{ $webhook->type === 'dingtalk' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' : '' }}
                            {{ $webhook->type === 'custom' ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' : '' }}">
                            {{ $webhook->typeLabel }}
                        </span>
                        <span class="text-xs {{ $webhook->is_active ? 'text-green-600' : 'text-zinc-400' }}">
                            {{ $webhook->is_active ? __('● 启用') : __('○ 停用') }}
                        </span>
                        @if($webhook->project)
                        <span class="text-xs text-zinc-500">{{ $webhook->project->title }}</span>
                        @else
                        <span class="text-xs text-zinc-400">{{ __('全局') }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-zinc-400 truncate">{{ $webhook->url }}</p>
                    @if($webhook->events)
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($webhook->events as $e)
                        <span class="text-xs text-zinc-500">{{ $availableEvents[$e] ?? $e }}</span>
                        @endforeach
                    </div>
                    @else
                    <span class="text-xs text-zinc-400">{{ __('所有事件') }}</span>
                    @endif
                </div>
                <div class="flex gap-1 shrink-0">
                    <button wire:click="toggleActive({{ $webhook->id }})"
                        class="p-1.5 rounded text-zinc-400 hover:text-zinc-600 transition-colors" title="{{ $webhook->is_active ? __('停用') : __('启用') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 12.728M12 3v3"/></svg>
                    </button>
                    <button wire:click="edit({{ $webhook->id }})"
                        class="p-1.5 rounded text-zinc-400 hover:text-sky-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    </button>
                    <button wire:click="delete({{ $webhook->id }})" wire:confirm="{{ __('确定删除此 Webhook？') }}"
                        class="p-1.5 rounded text-zinc-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
