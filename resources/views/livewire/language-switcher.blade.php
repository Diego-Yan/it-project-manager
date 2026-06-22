<div x-data="{ open: @entangle('showDropdown') }" x-on:click.outside="open = false" class="relative">
    <button @click="open = !open" :title="$wire.languages[$wire.current]?.native ?? ''"
        class="p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-xs font-medium uppercase tracking-wide"
        aria-label="{{ __('切换语言') }}">
        {{ $current === 'zh_CN' ? __('中') : __('EN') }}
    </button>

    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-36 bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50 origin-top-right">

        @foreach($languages as $code => $lang)
        <button wire:click="switchTo('{{ $code }}')"
            class="w-full text-left px-4 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors flex items-center justify-between gap-2
            {{ $current === $code ? 'text-sky-600 dark:text-sky-400 font-medium' : 'text-zinc-700 dark:text-zinc-300' }}">
            <span>{{ $lang['native'] }}</span>
            <span class="text-xs text-zinc-400">{{ strtoupper($code === 'zh_CN' ? 'zh' : 'en') }}</span>
        </button>
        @endforeach
    </div>
</div>
