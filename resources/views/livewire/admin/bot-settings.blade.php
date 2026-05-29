<div class="space-y-6">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">Bot 配置</h1><p class="text-sm text-zinc-500 mt-1">企业微信 / 钉钉群机器人，手机端直接发消息建工单、查状态</p></div>

    {{-- 企微 --}}
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
