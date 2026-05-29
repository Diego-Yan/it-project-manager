<div class="space-y-6">
    <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">AI 配置</h1><p class="text-sm text-zinc-500 mt-1">Embedding API 配置 — 知识库向量化搜索</p></div>

    @if(session('success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>@endif
    @if($testResult)<div class="p-3 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded-xl text-sky-700 dark:text-sky-400 text-sm">{{ $testResult }}</div>@endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <h2 class="text-base font-semibold mb-1">Embedding API</h2>
        <p class="text-sm text-zinc-500 mb-4">支持 OpenAI / 阿里云 DashScope / 硅基流动 / 本地 Ollama 等兼容接口。用于知识库文章的向量化嵌入，实现语义搜索。</p>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-medium text-zinc-500">API 地址</label>
                <input wire:model="embeddingUrl" placeholder="https://api.openai.com/v1/embeddings" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            </div>
            <div>
                <label class="text-xs font-medium text-zinc-500">API Key</label>
                <input wire:model="embeddingKey" type="password" placeholder="sk-..." class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            </div>
            <div>
                <label class="text-xs font-medium text-zinc-500">模型名称</label>
                <input wire:model="embeddingModel" placeholder="text-embedding-3-small" class="w-full mt-1 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            </div>
        </div>

        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl text-sm text-zinc-600 dark:text-zinc-400 mt-4">
            <p class="font-medium mb-2">常用 Embedding 服务：</p>
            <table class="w-full text-xs">
                <thead><tr class="text-left text-zinc-500"><th class="py-1 pr-4">服务商</th><th class="py-1 pr-4">API 地址</th><th class="py-1">模型</th></tr></thead>
                <tbody>
                    <tr><td class="py-1 pr-4">OpenAI</td><td class="py-1 pr-4 font-mono">https://api.openai.com/v1/embeddings</td><td class="py-1">text-embedding-3-small</td></tr>
                    <tr><td class="py-1 pr-4">阿里云 DashScope</td><td class="py-1 pr-4 font-mono">https://dashscope.aliyuncs.com/compatible-mode/v1/embeddings</td><td class="py-1">text-embedding-v3</td></tr>
                    <tr><td class="py-1 pr-4">硅基流动</td><td class="py-1 pr-4 font-mono">https://api.siliconflow.cn/v1/embeddings</td><td class="py-1">BAAI/bge-large-zh-v1.5</td></tr>
                    <tr><td class="py-1 pr-4">Ollama 本地</td><td class="py-1 pr-4 font-mono">http://localhost:11434/v1/embeddings</td><td class="py-1">nomic-embed-text</td></tr>
                </tbody>
            </table>
        </div>

        <div class="flex gap-2 mt-4">
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button>
            <button wire:click="test" class="px-4 py-2 text-sm text-sky-600 border border-sky-300 rounded-xl">测试连接</button>
        </div>
    </div>
</div>
