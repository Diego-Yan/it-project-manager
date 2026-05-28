<!DOCTYPE html>
<html lang="zh-CN" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('theme') !== 'light' }"
    :class="darkMode ? 'dark' : ''"
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
                {{-- 主题切换 --}}
                <button @click="darkMode=!darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                    class="p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
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
</body>
</html>
