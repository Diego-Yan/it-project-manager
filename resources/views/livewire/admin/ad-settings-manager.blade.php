<div class="space-y-6" x-data="{}">

    {{-- 成功/错误提示 --}}
    @if(session('success'))
    <div class="flex items-center gap-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- 页头 --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('AD 域认证配置') }}</h1>
            <p class="text-sm text-zinc-500 mt-0.5">{{ __('配置 Windows Active Directory 域认证，允许域账号直接登录本系统。') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="testConnection" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors disabled:opacity-60">
                <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="testConnection" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <span wire:loading.remove wire:target="testConnection">{{ __('测试连接') }}</span>
                <span wire:loading wire:target="testConnection">{{ __('连接中...') }}</span>
            </button>
            <button wire:click="save" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl bg-sky-600 hover:bg-sky-700 text-white transition-colors disabled:opacity-60 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="save">{{ __('保存配置') }}</span>
                <span wire:loading wire:target="save">{{ __('保存中...') }}</span>
            </button>
        </div>
    </div>

    {{-- 测试结果 --}}
    @if($testStatus === 'success')
    <div class="flex items-start gap-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ $testMessage }}</span>
    </div>
    @elseif($testStatus === 'fail')
    <div class="flex items-start gap-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <span>{{ $testMessage }}</span>
    </div>
    @elseif($testStatus === 'testing')
    <div class="flex items-center gap-3 bg-sky-50 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-800 text-sky-700 dark:text-sky-400 px-4 py-3 rounded-xl text-sm">
        <svg class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        {{ $testMessage }}
    </div>
    @endif

    {{-- 验证错误 --}}
    @if($errors->any())
    <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3 text-sm text-red-700 dark:text-red-400 space-y-1">
        @foreach($errors->all() as $error)
        <div>• {{ $error }}</div>
        @endforeach
    </div>
    @endif

    {{-- 基本开关 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <div>
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('启用 AD 域认证') }}</h2>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('开启后，用户可使用域账号登录；关闭则仅允许本地账号。') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model="adEnabled" class="sr-only peer">
                <div class="w-11 h-6 bg-zinc-200 dark:bg-zinc-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
            </label>
        </div>

        {{-- AD 启用时的提示 --}}
        @if($adEnabled)
        <div class="px-6 py-3 bg-sky-50 dark:bg-sky-950/20 border-b border-sky-100 dark:border-sky-900/50 text-xs text-sky-700 dark:text-sky-400 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {!! __('AD 认证已启用。登录页面将显示「AD 域账号登录」选项，域用户输入 <strong>域用户名</strong>（不含域名前缀）和密码即可登录。') !!}
        </div>
        @endif
    </div>

    {{-- 服务器配置 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('服务器连接') }}</h2>
            <p class="text-xs text-zinc-500 mt-0.5">{{ __('AD 服务器地址和通信协议配置。') }}</p>
        </div>
        <div class="px-6 py-5 space-y-4">

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('AD 服务器 IP / 主机名') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="adServer" placeholder="{{ __('如: 192.168.0.25 或 ad.company.com') }}"
                        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors @error('adServer') border-red-400 @enderror">
                    @error('adServer')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('端口') }} <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="adPort" placeholder="389"
                        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors">
                    <p class="mt-1 text-xs text-zinc-400">{{ __('标准: 389 / SSL: 636') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <input type="checkbox" wire:model="adUseTls" id="adUseTls"
                        class="w-4 h-4 rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                    <div>
                        <label for="adUseTls" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">{{ __('启用 TLS（STARTTLS）') }}</label>
                        <p class="text-xs text-zinc-400">{{ __('在 389 端口使用加密通信') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <input type="checkbox" wire:model="adUseSsl" id="adUseSsl"
                        class="w-4 h-4 rounded border-zinc-300 text-sky-600 focus:ring-sky-500">
                    <div>
                        <label for="adUseSsl" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">{{ __('启用 SSL（LDAPS）') }}</label>
                        <p class="text-xs text-zinc-400">{{ __('使用 LDAPS（端口通常为 636）') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- 域配置 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('域信息') }}</h2>
            <p class="text-xs text-zinc-500 mt-0.5">{{ __('Windows 域名和 LDAP 搜索基路径。') }}</p>
        </div>
        <div class="px-6 py-5 space-y-4">

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('域名（NetBIOS / FQDN）') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="adDomain" placeholder="{{ __('如: company.com 或 COMPANY') }}"
                    class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors @error('adDomain') border-red-400 @enderror">
                <p class="mt-1 text-xs text-zinc-400">{{ __('用于构造登录名：username@:domain', ['domain' => $adDomain ?: 'domain.com']) }}</p>
                @error('adDomain')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('搜索基路径（Base DN）') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="adBaseDn" placeholder="{{ __('如: DC=company,DC=com') }}"
                    class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors @error('adBaseDn') border-red-400 @enderror">
                <p class="mt-1 text-xs text-zinc-400">{{ __('LDAP 查询用户的起始路径，通常与域名对应。') }}</p>
                @error('adBaseDn')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    {{-- 管理员账号 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('查询账号') }}</h2>
            <p class="text-xs text-zinc-500 mt-0.5">{{ __('用于在 AD 中查询用户信息的账号（需要读取权限，建议使用专用服务账号）。') }}</p>
        </div>
        <div class="px-6 py-5 space-y-4">

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('管理员用户名') }}</label>
                <input type="text" wire:model="adAdminUsername" placeholder="{{ __('如: svc-ldap 或 administrator') }}"
                    class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors">
                <p class="mt-1 text-xs text-zinc-400">{{ __('填写域用户名（不含域名），登录时系统自动添加 @域名 后缀。') }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('管理员密码') }}</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show ? 'text' : 'password'" wire:model="adAdminPassword"
                        placeholder="{{ config('ad-auth.admin_password') ? __('（已设置，留空则不修改）') : __('请输入密码') }}"
                        class="w-full px-3 py-2.5 pr-10 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors">
                    <button type="button" @click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-zinc-400">{{ __('留空则不更改已存储的密码。密码将加密保存在服务器配置中。') }}</p>
            </div>

        </div>
    </div>

    {{-- 同步与安全设置 --}}
    <div class="grid grid-cols-2 gap-6">

        {{-- 用户同步 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('用户同步') }}</h2>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('域用户首次登录时的处理方式。') }}</p>
            </div>
            <div class="px-6 py-5 space-y-4">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('自动创建本地用户') }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">{{ __('域用户首次登录时自动在系统中建立账号') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="adAutoCreateUser" class="sr-only peer">
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-sky-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('新用户默认角色') }}</label>
                    <select wire:model="adDefaultRole"
                        class="w-full px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="user">{{ __('普通用户 (user)') }}</option>
                        <option value="manager">{{ __('项目经理 (manager)') }}</option>
                        <option value="admin">{{ __('管理员 (admin)') }}</option>
                    </select>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('同步 AD 用户组') }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">{{ __('自动根据 AD 组映射系统角色') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="adAutoSyncGroups" class="sr-only peer">
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-sky-600"></div>
                    </label>
                </div>

            </div>
        </div>

        {{-- 安全策略 --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('安全策略') }}</h2>
                <p class="text-xs text-zinc-500 mt-0.5">{{ __('登录失败保护和回退认证设置。') }}</p>
            </div>
            <div class="px-6 py-5 space-y-4">

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('AD 失败时回退本地认证') }}</p>
                        <p class="text-xs text-zinc-400 mt-0.5">{{ __('AD 不可用时允许使用本地账号登录') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="adFallbackToLocal" class="sr-only peer">
                        <div class="w-9 h-5 bg-zinc-200 dark:bg-zinc-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-sky-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('连续失败锁定次数') }}</label>
                    <div class="flex items-center gap-2">
                        <input type="number" wire:model="adLockAfterFailed" min="1" max="20"
                            class="w-24 px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <span class="text-sm text-zinc-500">{{ __('次失败后锁定账号') }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('锁定时长') }}</label>
                    <div class="flex items-center gap-2">
                        <input type="number" wire:model="adLockMinutes" min="1" max="1440"
                            class="w-24 px-3 py-2.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <span class="text-sm text-zinc-500">{{ __('分钟') }}</span>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- 帮助信息 --}}
    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/50 rounded-2xl px-6 py-4">
        <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            {{ __('配置参考') }}
        </h3>
        <div class="text-xs text-amber-700 dark:text-amber-400 space-y-1.5">
            {!! __('<p>• <strong>深圳 AD 主域控</strong>: 192.168.0.25 &nbsp;|&nbsp; <strong>备域控</strong>: 192.168.0.26</p>') !!}
            {!! __('<p>• <strong>杭州 AD 主域控</strong>: 10.10.0.8 &nbsp;|&nbsp; <strong>备域控</strong>: 10.10.0.10</p>') !!}
            {!! __('<p>• Base DN 示例: <code class="bg-amber-100 dark:bg-amber-900/50 px-1 rounded">DC=company,DC=com</code>（将 company 和 com 替换为实际域名各段）</p>') !!}
            {!! __('<p>• 填写后点击 <strong>「测试连接」</strong> 验证服务器是否可达，再点 <strong>「保存配置」</strong> 生效。</p>') !!}
        </div>
    </div>

</div>
