<div class="p-6 space-y-6">
    {{-- 页面标题 --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">用户管理</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">管理本地用户和 AD 域账号</p>
        </div>
        <button wire:click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            新建用户
        </button>
    </div>

    {{-- Flash 消息 --}}
    @if(session()->has('success'))
    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session()->has('error'))
    <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- 筛选栏 --}}
    <div class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-48">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="搜索姓名、用户名、邮箱、部门..."
                class="w-full pl-9 pr-4 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>

        <select wire:model.live="filterSource"
            class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            <option value="">全部来源</option>
            <option value="local">本地用户</option>
            <option value="ad">域账号</option>
        </select>

        <select wire:model.live="filterRole"
            class="px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            <option value="">全部角色</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}">{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- 用户列表 --}}
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">用户</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">来源</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">部门</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">角色</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">状态</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">最后登录</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @forelse($users as $user)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-400 flex items-center justify-center font-medium text-xs">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-400">{{ $user->username ?? $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->ad_authenticated)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            域账号
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            本地
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $user->department ?: '-' }}</td>
                    <td class="px-4 py-3">
                        @foreach($user->roles as $role)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-400">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleActive({{ $user->id }})"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium transition-colors
                                {{ $user->is_active
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                            {{ $user->is_active ? '启用' : '禁用' }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-xs text-zinc-400">
                        {{ $user->last_login_at ? $user->last_login_at->format('m-d H:i') : '从未' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button wire:click="openEditModal({{ $user->id }})"
                                class="p-1.5 text-zinc-400 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            @if($user->id !== auth()->id())
                            <button wire:click="confirmDelete({{ $user->id }})"
                                class="p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-zinc-400 dark:text-zinc-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p>没有找到符合条件的用户</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 分页 --}}
    @if($users->hasPages())
    <div>{{ $users->links() }}</div>
    @endif

{{-- ══ 新建/编辑用户 Modal ══ --}}
@if($showUserModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">

        {{-- 标题栏 --}}
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                @if($isAdUser)
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        编辑域账号
                    </span>
                @elseif($isEditing)
                    编辑本地用户
                @else
                    新建用户
                @endif
            </h2>
            <button wire:click="$set('showUserModal', false)" class="p-2 text-zinc-400 hover:text-zinc-600 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-4">

            {{-- ★ 新建时：账号类型选择 Tab --}}
            @if(!$isEditing)
            <div class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-700/50 rounded-xl">
                <button wire:click="switchCreateType('local')" type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2 px-3 text-sm font-medium rounded-lg transition-all
                        {{ $createType === 'local'
                            ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    本地用户
                </button>
                <button wire:click="switchCreateType('ad')" type="button"
                    class="flex-1 flex items-center justify-center gap-2 py-2 px-3 text-sm font-medium rounded-lg transition-all
                        {{ $createType === 'ad'
                            ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white shadow-sm'
                            : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                    AD 域账号
                </button>
            </div>
            @endif

            {{-- 域账号编辑提示 --}}
            @if($isAdUser)
            <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl text-sm text-purple-700 dark:text-purple-400">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-medium">域账号只读保护</p>
                    <p class="mt-0.5 opacity-80">AD 域账号的基本信息由域控服务器管理，此处仅可修改角色、部门和账号状态。</p>
                </div>
            </div>
            @endif

            {{-- ★ 新建 AD 账号：实时搜索 --}}
            @if(!$isEditing && $createType === 'ad')
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    搜索 AD 账号 <span class="text-red-500">*</span>
                    <span class="ml-1 text-xs font-normal text-zinc-400">（输入姓名、用户名或邮箱，至少2个字符）</span>
                </label>
                <div class="relative">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.400ms="adSearchKeyword"
                            type="text"
                            placeholder="输入姓名、用户名或邮箱搜索..."
                            autocomplete="off"
                            class="w-full pl-9 pr-10 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-sky-500
                                {{ $adSelectedUser ? 'border-green-400 dark:border-green-500' : '' }}">
                        {{-- 搜索中转圈 --}}
                        @if($adSearching)
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-sky-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </div>
                        @elseif($adSelectedUser)
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        @endif
                    </div>

                    {{-- 搜索结果下拉 --}}
                    @if(count($adSearchResults) > 0)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl max-h-56 overflow-y-auto">
                        @foreach($adSearchResults as $adUser)
                        <button wire:click="selectAdUser('{{ $adUser['username'] }}')"
                            type="button"
                            class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-sky-50 dark:hover:bg-sky-900/20 transition-colors border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center shrink-0 mt-0.5">
                                <span class="text-xs font-semibold text-purple-700 dark:text-purple-400">
                                    {{ mb_substr($adUser['name'], 0, 1) }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $adUser['name'] }}</span>
                                    <span class="text-xs text-zinc-400 shrink-0">{{ $adUser['username'] }}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-0.5">
                                    @if($adUser['department'])
                                    <span class="text-xs text-zinc-500">{{ $adUser['department'] }}</span>
                                    @endif
                                    @if($adUser['email'])
                                    <span class="text-xs text-zinc-400 truncate">{{ $adUser['email'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @elseif(mb_strlen($adSearchKeyword) >= 2 && !$adSearching && !$adSelectedUser)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                        未找到匹配的 AD 账号
                    </div>
                    @endif
                </div>
                @error('adSearchKeyword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 已选中后展示带出的信息（只读预览） --}}
            @if($adSelectedUser)
            <div class="p-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800 rounded-xl space-y-3">
                <p class="text-xs font-medium text-sky-700 dark:text-sky-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    已从 AD 域带出账号信息，确认无误后保存
                </p>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">姓名</span>
                        <p class="text-zinc-900 dark:text-white font-medium">{{ $formName ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">用户名</span>
                        <p class="text-zinc-900 dark:text-white font-medium">{{ $formUsername ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">邮箱</span>
                        <p class="text-zinc-700 dark:text-zinc-300 text-xs truncate">{{ $formEmail ?: '—' }}</p>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 text-xs">部门</span>
                        <p class="text-zinc-700 dark:text-zinc-300 text-xs">{{ $formDepartment ?: '—' }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- AD 账号的角色分配 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">角色</label>
                <select wire:model="formRole"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">-- 不分配角色 --</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 账号状态 --}}
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">账号状态</label>
                <button wire:click="$toggle('formIsActive')" type="button"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $formIsActive ? 'bg-sky-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $formIsActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm {{ $formIsActive ? 'text-green-600 dark:text-green-400' : 'text-zinc-500' }}">{{ $formIsActive ? '启用' : '禁用' }}</span>
            </div>

            @else {{-- 本地用户 或 编辑现有用户 --}}

            {{-- 姓名 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">姓名 <span class="text-red-500">*</span></label>
                <input wire:model="formName" type="text" {{ $isAdUser ? 'disabled' : '' }}
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed">
                @error('formName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 用户名 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">用户名 <span class="text-red-500">*</span></label>
                <input wire:model="formUsername" type="text" {{ $isAdUser ? 'disabled' : '' }}
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed">
                @error('formUsername') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 邮箱 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">邮箱</label>
                <input wire:model="formEmail" type="email" {{ $isAdUser ? 'disabled' : '' }}
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed">
                @error('formEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 密码（仅本地用户显示） --}}
            @if(!$isAdUser)
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    密码 @if($isEditing)<span class="text-xs text-zinc-400 font-normal">（留空不修改）</span>@else<span class="text-red-500">*</span>@endif
                </label>
                <input wire:model="formPassword" type="password" autocomplete="new-password"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                @error('formPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- 手机号（仅本地用户） --}}
            @if(!$isAdUser)
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">手机号</label>
                <input wire:model="formPhone" type="text"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                @error('formPhone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            @endif

            {{-- 部门 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">部门</label>
                <input wire:model="formDepartment" type="text"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                @error('formDepartment') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 角色 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">角色</label>
                <select wire:model="formRole"
                    class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">-- 不分配角色 --</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                @error('formRole') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 状态 --}}
            <div class="flex items-center gap-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">账号状态</label>
                <button wire:click="$toggle('formIsActive')" type="button"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $formIsActive ? 'bg-sky-600' : 'bg-zinc-300 dark:bg-zinc-600' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $formIsActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm {{ $formIsActive ? 'text-green-600 dark:text-green-400' : 'text-zinc-500' }}">{{ $formIsActive ? '启用' : '禁用' }}</span>
            </div>

            @endif {{-- end if createType === 'ad' --}}

        </div>

        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
            <button wire:click="$set('showUserModal', false)"
                class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                取消
            </button>
            <button wire:click="saveUser"
                class="px-4 py-2 text-sm font-medium bg-sky-600 hover:bg-sky-700 text-white rounded-xl transition-colors">
                @if(!$isEditing && $createType === 'ad')
                    添加域账号
                @elseif($isEditing)
                    保存修改
                @else
                    创建用户
                @endif
            </button>
        </div>
    </div>
</div>
@endif


{{-- ══ 删除确认 Modal ══ --}}
@if($showDeleteModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">确认删除用户</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">此操作不可恢复，请谨慎操作。</p>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button wire:click="$set('showDeleteModal', false)"
                class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                取消
            </button>
            <button wire:click="deleteUser"
                class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">
                确认删除
            </button>
        </div>
    </div>
</div>
@endif

</div>
