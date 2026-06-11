<div class="relative" x-data x-on:click.outside="$wire.showDropdown = false">
    {{-- Bell button --}}
    <button wire:click="toggleDropdown"
        class="relative p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if($unreadCount > 0)
        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    {{-- Dropdown --}}
    @if($showDropdown)
    <div class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">通知</h3>
            @if($unreadCount > 0)
            <button wire:click="markAllRead" class="text-xs text-sky-600 dark:text-sky-400 hover:underline">全部已读</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @if($notifications->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-zinc-400">暂无通知</div>
            @else
            @foreach($notifications as $n)
            <div class="px-4 py-3 border-b border-zinc-50 dark:border-zinc-800/50 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors {{ $n->is_read ? 'opacity-60' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $n->is_read ? 'bg-zinc-300' : 'bg-'.$n->typeColor.'-500' }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-zinc-200">{{ $n->title }}</p>
                        @if($n->body)
                        <p class="text-xs text-zinc-500 mt-0.5 line-clamp-2">{{ $n->body }}</p>
                        @endif
                        <p class="text-xs text-zinc-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$n->is_read)
                    <button wire:click="markRead({{ $n->id }})" class="text-xs text-zinc-400 hover:text-sky-500 shrink-0" title="标记已读">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
    @endif
</div>
