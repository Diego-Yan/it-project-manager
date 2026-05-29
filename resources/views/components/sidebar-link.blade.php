@props(['route', 'icon'])

<a href="{{ route($route) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
    {{ request()->routeIs($route) || ($route === 'projects.index' && request()->routeIs('projects.*'))
        ? 'bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-400'
        : 'text-zinc-700 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white' }}">
    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/>
    </svg>
    <span>{{ $slot }}</span>
</a>
