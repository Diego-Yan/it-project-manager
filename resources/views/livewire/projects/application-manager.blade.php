<div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
    @if(session('app_success'))
    <div class="mb-3 text-xs text-green-600 dark:text-green-400">{{ session('app_success') }}</div>
    @endif
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">
        {{ __('加入申请') }}
        @if($applications->isNotEmpty())
        <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-xs">{{ $applications->count() }}</span>
        @endif
    </h3>

    @if($applications->isEmpty())
    <p class="text-xs text-zinc-400">{{ __('暂无待处理的申请') }}</p>
    @else
    <div class="space-y-2">
        @foreach($applications as $app)
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
            <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/40 flex items-center justify-center text-sky-600 text-xs font-semibold shrink-0">
                {{ mb_substr($app->user->name, 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $app->user->name }}</p>
                <p class="text-xs text-zinc-500">{{ $app->user->department ?? '—' }}</p>
            </div>
            <div class="flex gap-1 shrink-0">
                <button wire:click="approve({{ $app->id }})"
                    class="px-2.5 py-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-950/40 hover:bg-green-200 rounded-lg transition-colors">
                    {{ __('通过') }}
                </button>
                <button wire:click="reject({{ $app->id }})"
                    class="px-2.5 py-1 text-xs text-red-500 hover:text-red-600 rounded-lg transition-colors">
                    {{ __('拒绝') }}
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
