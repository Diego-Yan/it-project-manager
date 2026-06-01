{{-- 侧边导航 — 可折叠分组 --}}
<div class="flex flex-col h-full" x-data="{
    collapsed: JSON.parse(localStorage.getItem('sidebar_collapsed') || '{}'),

    toggle(key) {
        this.collapsed[key] = !this.collapsed[key];
        localStorage.setItem('sidebar_collapsed', JSON.stringify(this.collapsed));
    },

    isOpen(key) { return !this.collapsed[key]; }
}">

    <div class="h-16 flex items-center gap-3 px-5 border-b border-zinc-200 dark:border-zinc-800 shrink-0">
        <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
        </div>
        <div class="min-w-0"><p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">IT 服务管理</p><p class="text-xs text-zinc-500 truncate">IT Service Management</p></div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

        {{-- ═══ 工作台 ═══ --}}
        <button @click="toggle('workbench')" class="w-full flex items-center gap-2 px-3 mb-1 text-sm font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
            <svg :class="isOpen('workbench') ? 'rotate-90' : ''" class="w-3 h-3 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            工作台
        </button>
        <div x-show="isOpen('workbench')" x-collapse>
            <x-sidebar-link route="dashboard" icon='M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'>仪表盘</x-sidebar-link>
            <x-sidebar-link route="my.tasks" icon='M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'>
                我的任务
                @if($sidebarPendingTasks > 0)<span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-amber-500 text-white text-xs font-bold">{{ $sidebarPendingTasks }}</span>@endif
            </x-sidebar-link>
            <x-sidebar-link route="my.tickets" icon='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'>
                我的工单
                @if($sidebarOpenTickets > 0)<span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $sidebarOpenTickets }}</span>@endif
            </x-sidebar-link>
            <x-sidebar-link route="my.projects" icon='M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'>我的项目</x-sidebar-link>
            <x-sidebar-link route="my.assets" icon='M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'>
                我的资产
                @if($sidebarWarrantySoon > 0)<span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $sidebarWarrantySoon }}</span>@endif
            </x-sidebar-link>
        </div>

        {{-- ═══ 项目管理 ═══ --}}
        <button @click="toggle('projects')" class="w-full flex items-center gap-2 px-3 mt-4 mb-1 text-sm font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
            <svg :class="isOpen('projects') ? 'rotate-90' : ''" class="w-3 h-3 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            项目管理
        </button>
        <div x-show="isOpen('projects')" x-collapse>
            <x-sidebar-link route="projects.index" icon='M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z'>全部项目</x-sidebar-link>
            @can('view categories')<x-sidebar-link route="categories.index" icon='M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z'>项目分类</x-sidebar-link>@endcan
            @can('view categories')<x-sidebar-link route="admin.regions" icon='M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'>地区管理</x-sidebar-link>@endcan
        </div>

        {{-- ═══ ITSM 服务管理 ═══ --}}
        @canany(['manage tickets','manage assets','edit knowledge','approve changes','manage incidents','manage slas'])
        <button @click="toggle('itsm')" class="w-full flex items-center gap-2 px-3 mt-4 mb-1 text-sm font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
            <svg :class="isOpen('itsm') ? 'rotate-90' : ''" class="w-3 h-3 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            ITSM 服务管理
        </button>
        <div x-show="isOpen('itsm')" x-collapse>
            @php $tc = $sidebarTotalOpenTickets; @endphp
            <x-sidebar-link route="itsm.tickets" icon='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'>
                工单管理
                @if($tc > 0)<span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $tc }}</span>@endif
            </x-sidebar-link>
            <x-sidebar-link route="itsm.assets" icon='M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'>资产管理</x-sidebar-link>
            <x-sidebar-link route="itsm.knowledge" icon='M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'>知识库</x-sidebar-link>
            <x-sidebar-link route="itsm.services" icon='M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z'>服务目录</x-sidebar-link>
            <x-sidebar-link route="itsm.changes" icon='M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'>变更管理</x-sidebar-link>
            @php $oic = $sidebarOpenIncidents; @endphp
            <x-sidebar-link route="itsm.incidents" icon='M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'>
                故障管理
                @if($oic > 0)<span class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $oic }}</span>@endif
            </x-sidebar-link>
            <x-sidebar-link route="itsm.zabbix" icon='M10.5 6h3m-3 0a1.5 1.5 0 110 3h-3a1.5 1.5 0 110-3zm0 0V3m0 9v3m3-3a1.5 1.5 0 110 3h-3a1.5 1.5 0 110-3zm0 0V9m3-3a1.5 1.5 0 110 3m0 0V3'>Zabbix 集成</x-sidebar-link>
            <x-sidebar-link route="itsm.slas" icon='M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'>SLA 管理</x-sidebar-link>
        </div>
        @endcanany

        {{-- ═══ 系统管理 ═══ --}}
        @canany(['view users', 'manage roles'])
        <button @click="toggle('admin')" class="w-full flex items-center gap-2 px-3 mt-4 mb-1 text-sm font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
            <svg :class="isOpen('admin') ? 'rotate-90' : ''" class="w-3 h-3 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            系统管理
        </button>
        <div x-show="isOpen('admin')" x-collapse>
            @can('view users')<x-sidebar-link route="admin.users" icon='M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'>用户管理</x-sidebar-link>@endcan
            <x-sidebar-link route="admin.ad-settings" icon='M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'>AD 域配置</x-sidebar-link>
            <x-sidebar-link route="admin.im" icon='M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'>IM 接入</x-sidebar-link>
            <x-sidebar-link route="admin.webhooks" icon='M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0'>通知 Webhook</x-sidebar-link>
            @can('manage roles')<x-sidebar-link route="admin.ai" icon='M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z'>AI 配置</x-sidebar-link>@endcan
            @can('manage roles')<x-sidebar-link route="admin.roles" icon='M9 12.75l2.25 2.25 4.5-4.5M10.5 3a7.5 7.5 0 100 15 7.5 7.5 0 000-15zm7.5 7.5a2.5 2.5 0 100 5 2.5 2.5 0 000-5zm0 5v3m0 0h-1.5m1.5 0h1.5'>角色管理</x-sidebar-link>@endcan
        </div>
        @endcanany
    </nav>

    {{-- 底部用户信息 --}}
    <div class="shrink-0 border-t border-zinc-200 dark:border-zinc-800 p-3">
        <div class="flex items-center gap-3 px-2 py-2">
            <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center text-white text-sm font-semibold shrink-0">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            <div class="flex-1 min-w-0"><p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p><p class="text-xs text-zinc-500 truncate">{{ auth()->user()->getRoleNamesString }}</p></div>
        </div>
    </div>
</div>
