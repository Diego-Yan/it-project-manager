<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('Zabbix 集成') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('对接 Zabbix 监控，告警自动生成故障工单') }}</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">{{ __('+ 添加 Zabbix') }}</button>
    </div>

    @if($testResult)<div class="p-3 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl text-sky-700 dark:text-sky-400 text-sm">{{ $testResult }}</div>@endif

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? __('编辑 Zabbix') : __('添加 Zabbix') }}</h3>
        <div class="grid sm:grid-cols-2 gap-3">
            <input wire:model="formName" placeholder="{{ __('名称: 深圳生产环境') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input wire:model="formUrl" placeholder="{{ __('URL: https://zbx.example.com/api_jsonrpc.php') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-2">
            <input wire:model="formApiToken" placeholder="API Token" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-2">
            @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            @error('formUrl')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            @error('formApiToken')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            <div>
                <label class="text-xs font-medium text-zinc-500">{{ __('最低告警级别') }}</label>
                <select wire:model="formMinSeverity" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="3">{{ __('一般严重') }}</option><option value="4">{{ __('严重') }}</option><option value="5">{{ __('灾难') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-zinc-500">{{ __('轮询间隔 (分钟)') }}</label>
                <input type="number" wire:model="formPollInterval" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            </div>
        </div>
        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formIsActive" class="rounded"> {{ __('启用') }}</label>
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">{{ __('取消') }}</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存') }}</button></div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($configs->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无 Zabbix 连接') }}</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($configs as $c)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $c->name }}</span>
                        <span class="text-xs {{ $c->is_active ? 'text-green-600' : 'text-zinc-400' }}">{{ $c->is_active ? __('● 启用') : __('○ 停用') }}</span>
                        <span class="text-xs text-zinc-500">{{ __('级别 ≥ ') }}{{ App\Services\ZabbixService::severityLabel($c->min_severity) }}</span>
                        <span class="text-xs text-zinc-400">{{ __('每 ') }}{{ $c->poll_interval }}{{ __('分钟') }}</span>
                    </div>
                    <p class="text-xs text-zinc-400 truncate">{{ $c->url }}</p>
                    @if($c->last_poll_at)<p class="text-xs text-zinc-500">{{ __('上次轮询: ') }}{{ $c->last_poll_at->format('m/d H:i') }}</p>@endif
                </div>
                <div class="flex gap-1 shrink-0">
                    <button wire:click="test({{ $c->id }})" class="px-3 py-1.5 text-xs text-sky-600 border border-sky-300 rounded-lg hover:bg-sky-50">{{ __('测试') }}</button>
                    <button wire:click="edit({{ $c->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                    <button wire:click="delete({{ $c->id }})" wire:confirm="{{ __('确定删除？') }}" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400">
        <p class="font-medium mb-2">{{ __('Cron 定时任务:') }}</p>
        <code class="block p-3 bg-zinc-100 dark:bg-zinc-900 rounded-lg font-mono text-xs">* * * * * cd {{ base_path() }} && php artisan zabbix:poll >> storage/logs/zabbix.log</code>
        <p class="text-xs mt-2 text-zinc-500">{{ __('命令会检查所有启用的 Zabbix 连接，将新告警生成工单（已有工单的去重跳过）') }}</p>
    </div>
</div>
