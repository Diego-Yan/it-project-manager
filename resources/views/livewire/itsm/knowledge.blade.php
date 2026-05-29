<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">知识库</h1><p class="text-sm text-zinc-500 mt-1">常见问题解决方案 · 操作手册 · 经验沉淀</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 写文章</button>
    </div>

    <div class="flex gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" placeholder="搜索知识库..." class="flex-1 px-4 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑文章' : '撰写文章' }}</h3>
        <input wire:model="formTitle" placeholder="文章标题*" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3">
        <select wire:model="formCategory" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3"><option value="general">通用</option><option value="hardware">硬件</option><option value="software">软件</option><option value="network">网络</option><option value="account">账号</option><option value="printer">打印</option></select>
        <textarea wire:model="formContent" rows="6" placeholder="文章内容* (支持 Markdown 格式)" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3"></textarea>
        <input wire:model="formTags" placeholder="标签，逗号分隔" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3">
        <div class="flex gap-2 justify-end"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">发布</button></div>
    </div>
    @endif

    {{-- 文章阅读 --}}
    @if($viewArticle)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <button wire:click="$set('viewId', null)" class="text-xs text-zinc-400 hover:text-sky-500 mb-3">← 返回列表</button>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">{{ $viewArticle->title }}</h2>
        <span class="text-xs text-zinc-500">{{ $viewArticle->categoryLabel }} · {{ $viewArticle->author->name }} · {{ $viewArticle->created_at->format('Y-m-d') }} · {{ $viewArticle->view_count }} 次阅读</span>
        <div class="mt-4 prose dark:prose-invert text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $viewArticle->content }}</div>
    </div>
    @else
    <div class="grid md:grid-cols-2 gap-4">
        @foreach($articles as $a)
        <button wire:click="view({{ $a->id }})" class="text-left bg-white dark:bg-zinc-900 rounded-2xl border p-5 hover:border-sky-300 dark:hover:border-sky-700 transition-all">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">{{ $a->title }}</h3>
            <p class="text-xs text-zinc-500 line-clamp-2 mb-3">{{ Str::limit(strip_tags($a->content), 120) }}</p>
            <div class="flex items-center gap-2 text-xs text-zinc-400">
                <span class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800">{{ $a->categoryLabel }}</span>
                <span>· {{ $a->author->name }}</span>
                <span>· {{ $a->view_count }} 次阅读</span>
            </div>
        </button>
        @endforeach
    </div>
    @endif
    @if($articles->hasPages() && !$viewArticle)<div class="flex justify-center">{{ $articles->links() }}</div>@endif
</div>
