<!DOCTYPE html>
<html lang="zh-CN"
    x-data="{ sidebarOpen: false, theme: 'system' }"
    x-init="
        const el = $el;
        theme = localStorage.getItem('theme') || 'system';

        function applyTheme() {
            const isDark = theme === 'system'
                ? window.matchMedia('(prefers-color-scheme: dark)').matches
                : theme === 'dark';
            el.classList.toggle('dark', isDark);
        }

        applyTheme();

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (theme === 'system') applyTheme();
        });

        $watch('theme', () => {
            localStorage.setItem('theme', theme);
            applyTheme();
        });
    "
    class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} @isset($title) - {{ $title }} @endisset</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-enter { transform: translateX(-100%); }
        .sidebar-enter-active { transition: transform 0.25s ease; }
    </style>
</head>
<body class="h-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">
<div class="flex h-full">

    {{-- ── 侧边导航 ────────────────────────────────────────── --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 shrink-0">
        @include('layouts.sidebar')
    </aside>

    {{-- 移动端遮罩 --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden backdrop-blur-sm"></div>

    {{-- 移动端侧边栏 --}}
    <aside x-show="sidebarOpen" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 z-40 flex flex-col lg:hidden shadow-2xl">
        @include('layouts.sidebar')
    </aside>

    {{-- ── 主内容区 ────────────────────────────────────────── --}}
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

        {{-- 顶部导航栏 --}}
        <header class="h-16 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-4 px-4 lg:px-6 shrink-0">
            <button @click="sidebarOpen=true" class="lg:hidden p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex-1">
                @isset($title)
                <h1 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
                @endisset
            </div>

            <div class="flex items-center gap-3">
                {{-- 通知中心 --}}
                @livewire(\App\Livewire\NotificationBell::class)

                {{-- 主题切换: light → dark → system --}}
                <button @click="theme = theme === 'light' ? 'dark' : theme === 'dark' ? 'system' : 'light'"
                    :title="{ light: '浅色', dark: '深色', system: '跟随系统' }[theme]"
                    class="p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                    <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 007.002-3.998z"/>
                    </svg>
                    <svg x-show="theme === 'system'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                    </svg>
                </button>

                {{-- 用户菜单 --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open=!open" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center text-white text-sm font-semibold">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <span class="hidden sm:block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak @click.away="open=false"
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                        <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-700">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-zinc-500">{{ auth()->user()->getRoleNamesString }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                退出登录
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- 页面内容 --}}
        <main class="flex-1 overflow-y-auto">
            <div class="p-4 lg:p-6">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>

@fluxScripts
@livewire(\App\Livewire\AiChat::class)
</body>
</html>
