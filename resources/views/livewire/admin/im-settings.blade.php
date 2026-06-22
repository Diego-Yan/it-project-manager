<div class="space-y-6">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('企微 / 钉钉 集成') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('自建应用接入：通讯录同步 + 消息接收 + 自动建工单') }}</p></div>

    @if(session('success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>@endif
    @if($testResult)<div class="p-3 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl text-sky-700 dark:text-sky-400 text-sm">{{ $testResult }}</div>@endif
    @if($syncResult)<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ $syncResult }}</div>@endif

    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-4 text-sm text-amber-800 dark:text-amber-300">
        {!! __('<strong>不是群机器人：</strong>群机器人只能发消息。要接收消息和读通讯录，需要<strong>自建应用</strong>（企业微信）或<strong>企业内部应用</strong>（钉钉）。') !!}
    </div>

    {{-- ═══ 企业微信 ═══ --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <h2 class="text-base font-semibold flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-950/40 flex items-center justify-center text-green-600 text-sm">{{ __('微') }}</span>{{ __('企业微信 · 自建应用') }}</h2>
        {!! __('<p class="text-sm text-zinc-500 mt-2">在 <a href="https://work.weixin.qq.com/wework_admin/frame#apps" class="text-sky-500 underline" target="_blank">企微管理后台 → 应用管理 → 自建应用</a> 创建应用，获取 Corp ID 和 Secret。</p>') !!}

        <div class="grid sm:grid-cols-2 gap-3 mt-4">
            <div><label class="text-xs font-medium text-zinc-500">Corp ID</label><input wire:model="wechatCorpId" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
            <div><label class="text-xs font-medium text-zinc-500">Corp Secret</label><input wire:model="wechatCorpSecret" type="password" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button wire:click="saveWechat" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存') }}</button>
            <button wire:click="testWechat" class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-xl">{{ __('测试连接') }}</button>
            <button wire:click="syncWechatUsers" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-xl">{{ __('同步通讯录') }}</button>
            @if($wechatUserCount > 0)<span class="text-sm text-green-600 ml-2">{{ __('已同步 :count 人', ['count' => $wechatUserCount]) }}</span>@endif
        </div>

        <div class="mt-4 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400">
            <p class="font-medium mb-1">{{ __('消息接收配置：') }}</p>
            {!! __('<p>自建应用 → 接收消息 → 设置 API 接收 → URL 填：<code class="bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-xs">:url</code></p>', ['url' => $wechatUrl]) !!}
        </div>
    </div>

    {{-- ═══ 钉钉 ═══ --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <h2 class="text-base font-semibold flex items-center gap-2"><span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 text-sm">{{ __('钉') }}</span>{{ __('钉钉 · 企业内部应用') }}</h2>
        {!! __('<p class="text-sm text-zinc-500 mt-2">在 <a href="https://open.dingtalk.com/" class="text-sky-500 underline" target="_blank">钉钉开放平台 → 应用开发 → 企业内部应用</a> 创建应用，获取 App Key 和 Secret。</p>') !!}

        <div class="grid sm:grid-cols-2 gap-3 mt-4">
            <div><label class="text-xs font-medium text-zinc-500">App Key</label><input wire:model="dingtalkAppKey" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
            <div><label class="text-xs font-medium text-zinc-500">App Secret</label><input wire:model="dingtalkAppSecret" type="password" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <button wire:click="saveDingtalk" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存') }}</button>
            <button wire:click="testDingtalk" class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-xl">{{ __('测试连接') }}</button>
            <button wire:click="syncDingtalkUsers" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-xl">{{ __('同步通讯录') }}</button>
            @if($dingtalkUserCount > 0)<span class="text-sm text-green-600 ml-2">{{ __('已同步 :count 人', ['count' => $dingtalkUserCount]) }}</span>@endif
        </div>

        <div class="mt-4 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400">
            <p class="font-medium mb-1">{{ __('消息接收配置：') }}</p>
            {!! __('<p>应用 → 机器人 → 消息接收模式 → HTTP 模式 → URL 填：<code class="bg-zinc-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-xs">:url</code></p>', ['url' => $dingtalkUrl]) !!}
        </div>
    </div>

    {{-- 机器人命令 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <h2 class="text-base font-semibold mb-3">{{ __('消息命令') }}</h2>
        <div class="space-y-2 text-sm">
            <div class="flex items-start gap-3"><span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">3楼打印机没墨了</span><span class="text-zinc-500">{{ __('→ 自动创建工单') }}</span></div>
            <div class="flex items-start gap-3"><span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">状态 5</span><span class="text-zinc-500">{{ __('→ 查询 #5 工单进度') }}</span></div>
            <div class="flex items-start gap-3"><span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">紧急 服务器宕机了</span><span class="text-zinc-500">{{ __('→ 创建高优先级工单') }}</span></div>
        </div>
    </div>
</div>
