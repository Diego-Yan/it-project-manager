<div class="space-y-6">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">Bot 配置</h1><p class="text-sm text-zinc-500 mt-1">企业微信 / 钉钉群机器人 + 用户同步</p></div>

    @if(session('success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>@endif
    @if($testResult)<div class="p-3 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl text-sky-700 dark:text-sky-400 text-sm">{{ $testResult }}</div>@endif

    {{-- 企微 API 凭证 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-950/40 flex items-center justify-center text-green-600 text-sm">微</span>
            企业微信 API 凭证
        </h2>
        <p class="text-sm text-zinc-500 mt-2">配置后可以同步企业微信通讯录用户，Bot 消息也能自动匹配用户身份。</p>
        <div class="grid sm:grid-cols-2 gap-3 mt-4">
            <div><label class="text-xs font-medium text-zinc-500">Corp ID（企业ID）</label><input wire:model="wechatCorpId" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
            <div><label class="text-xs font-medium text-zinc-500">Corp Secret（应用密钥）</label><input wire:model="wechatCorpSecret" type="password" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
        </div>
        <div class="flex gap-2 mt-3">
            <button wire:click="saveWechat" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button>
            <button wire:click="testWechat" class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-xl">测试连接</button>
        </div>
    </div>

    {{-- 钉钉 API 凭证 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 text-sm">钉</span>
             钉钉 API 凭证
        </h2>
        <p class="text-sm text-zinc-500 mt-2">配置后可以同步钉钉通讯录用户，Bot 消息也能自动匹配用户身份。</p>
        <div class="grid sm:grid-cols-2 gap-3 mt-4">
            <div><label class="text-xs font-medium text-zinc-500">App Key（应用Key）</label><input wire:model="dingtalkAppKey" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
            <div><label class="text-xs font-medium text-zinc-500">App Secret（应用密钥）</label><input wire:model="dingtalkAppSecret" type="password" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></div>
        </div>
        <div class="flex gap-2 mt-3">
            <button wire:click="saveDingtalk" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button>
            <button wire:click="testDingtalk" class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-xl">测试连接</button>
        </div>
    </div>

    {{-- 企微 Bot --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-950/40 flex items-center justify-center text-green-600 text-sm">微</span>
            企业微信
        </h2>
        <p class="text-sm text-zinc-500 mt-2">在企微管理后台添加群机器人，配置回调地址。员工在群里 @机器人 发送消息即可自动创建工单。</p>

        <div class="mt-4 space-y-3">
            <div>
                <span class="text-xs font-medium text-zinc-500">回调 URL</span>
                <div class="flex items-center gap-2 mt-1">
                    <code class="flex-1 px-3 py-2 text-sm bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono text-zinc-700 dark:text-zinc-300 break-all">{{ $wechatUrl }}</code>
                </div>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400">
                <p class="font-medium mb-1">使用方法：</p>
                <ol class="list-decimal ml-4 space-y-1">
                    <li>在企业微信管理后台 → 应用管理 → 群机器人 → 添加</li>
                    <li>配置消息回调地址为上方的 URL</li>
                    <li>在群里 @机器人 发送报修内容，如 "3楼打印机故障"</li>
                    <li>机器人自动创建工单并回复工单号</li>
                    <li>发送「状态 工单号」查询进度</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- 钉钉 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 text-sm">钉</span>
            钉钉
        </h2>
        <p class="text-sm text-zinc-500 mt-2">在钉钉开放平台创建机器人应用，配置消息接收地址。员工在群里发送消息即可自动创建工单。</p>

        <div class="mt-4 space-y-3">
            <div>
                <span class="text-xs font-medium text-zinc-500">消息接收地址</span>
                <div class="flex items-center gap-2 mt-1">
                    <code class="flex-1 px-3 py-2 text-sm bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono text-zinc-700 dark:text-zinc-300 break-all">{{ $dingtalkUrl }}</code>
                </div>
            </div>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400">
                <p class="font-medium mb-1">使用方法：</p>
                <ol class="list-decimal ml-4 space-y-1">
                    <li>在钉钉开放平台 → 创建机器人应用</li>
                    <li>配置机器人消息接收地址为上方的 URL</li>
                    <li>将机器人添加到项目群</li>
                    <li>员工在群里 @机器人 发送报修内容</li>
                    <li>发送「状态 工单号」查询进度</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- 使用说明 --}}
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">机器人命令</h2>
        <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
                <span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">3楼打印机没墨了</span>
                <span class="text-zinc-500">→ 自动创建工单（包含"紧急"关键词自动设为高优先级）</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">状态 5</span>
                <span class="text-zinc-500">→ 查询 #5 工单的处理进度</span>
            </div>
            <div class="flex items-start gap-3">
                <span class="shrink-0 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">紧急 服务器宕机了</span>
                <span class="text-zinc-500">→ 创建高优先级工单</span>
            </div>
        </div>
    </div>
</div>
