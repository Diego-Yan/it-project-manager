<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">知识库</h1><p class="text-sm text-zinc-500 mt-1">常见问题 · 操作手册 · 经验沉淀 · 文件归档</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 写文章</button>
    </div>

    @if(session('kb_success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('kb_success') }}</div>@endif

    <div class="flex gap-3 items-center flex-wrap">
        <input wire:model.live.debounce.300ms="search" placeholder="搜索文章标题或内容..." class="flex-1 min-w-48 px-4 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
        <select wire:model.live="filterTag" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <option value="">全部标签</option>
            @foreach($allTags as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->count }})</option>@endforeach
        </select>
    </div>

    @if($allTags->isNotEmpty())
    <div class="flex gap-2 flex-wrap">
        @foreach($allTags->take(15) as $t)
        <button wire:click="$set('filterTag', '{{ $filterTag == $t->id ? '' : $t->id }}')"
            class="px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors
                {{ $filterTag == $t->id ? 'border-'.$t->color.'-500 bg-'.$t->color.'-50 dark:bg-'.$t->color.'-950/30 text-'.$t->color.'-700' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:border-zinc-300' }}">
            #{{ $t->name }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- 写文章表单 --}}
    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑文章' : '撰写文章' }}</h3>
        <input wire:model="formTitle" placeholder="文章标题*" class="w-full px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3">
        <div class="grid sm:grid-cols-2 gap-3 mb-3">
            <select wire:model="formCategory" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                <option value="general">通用</option><option value="hardware">硬件</option><option value="software">软件</option><option value="network">网络</option><option value="account">账号</option><option value="printer">打印</option>
            </select>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">附件 (PDF/Word/PPT/图片)</label>
                <input type="file" wire:model="uploadFile" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov"
                    class="w-full text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-sky-50 dark:file:bg-sky-950/40 file:text-sky-700 dark:file:text-sky-400">
                <div wire:loading wire:target="uploadFile" class="text-xs text-sky-500 mt-1">上传中...</div>
                @error('uploadFile')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <textarea wire:model="formContent" rows="6" placeholder="文章内容* (支持 Markdown)" class="w-full px-3 min-h-[5rem] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-3"></textarea>
        {{-- 标签选择 --}}
        <div class="mb-3">
            <label class="block text-xs font-medium text-zinc-500 mb-2">标签</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($allTags as $t)
                <label class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs border cursor-pointer transition-colors
                    {{ in_array($t->id, $selectedTagIds) ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400' : 'border-zinc-200 dark:border-zinc-700 text-zinc-500' }}">
                    <input type="checkbox" wire:model="selectedTagIds" value="{{ $t->id }}" class="sr-only">
                    #{{ $t->name }}
                </label>
                @endforeach
            </div>
        </div>
        @error('formTitle')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror
        @error('formContent')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror
        <div class="flex gap-2 justify-end"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">发布</button></div>
    </div>
    @endif

    {{-- 文章详情 --}}
    @if($viewArticle)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6">
        <button wire:click="$set('viewId', null)" class="text-xs text-zinc-400 hover:text-sky-500 mb-3">← 返回列表</button>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">{{ $viewArticle->title }}</h2>
        <div class="flex items-center gap-2 flex-wrap text-xs text-zinc-500 mb-4">
            <span class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800">{{ $viewArticle->categoryLabel }}</span>
            <span>· {{ $viewArticle->author->name }}</span>
            <span>· {{ $viewArticle->created_at->format('Y-m-d') }}</span>
            <span>· {{ $viewArticle->view_count }} 次阅读</span>
            @foreach($viewArticle->kbTags as $t)
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-{{ $t->color }}-50 dark:bg-{{ $t->color }}-950/30 text-{{ $t->color }}-600 dark:text-{{ $t->color }}-400">#{{ $t->name }}</span>
            @endforeach
        </div>
        {{-- 附件 --}}
        @if($viewArticle->attachments->isNotEmpty())
        <div class="mb-4 space-y-2">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">附件 ({{ $viewArticle->attachments->count() }})</p>
            @foreach($viewArticle->attachments as $att)
            <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                @if($att->preview_type === 'image')
                <img src="{{ asset('storage/'.$att->file_path) }}" class="w-12 h-12 rounded-lg object-cover">
                @else
                <div class="w-12 h-12 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-lg">{{ match($att->preview_type) { 'pdf'=>'📄', 'office'=>'📊', 'video'=>'🎬', default=>'📎' } }}</div>
                @endif
                <div class="flex-1 min-w-0"><p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $att->file_name }}</p><p class="text-xs text-zinc-500">{{ $att->file_size_human }}</p></div>
                <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" class="px-3 py-1.5 text-xs bg-sky-600 text-white rounded-lg hover:bg-sky-500">下载/预览</a>
            </div>
            @endforeach
        </div>
        @endif
        <div class="prose dark:prose-invert text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $viewArticle->content }}</div>
    </div>
    @else
    <div class="grid md:grid-cols-2 gap-4">
        @foreach($articles as $a)
        <button wire:click="view({{ $a->id }})" class="text-left bg-white dark:bg-zinc-900 rounded-2xl border p-5 hover:border-sky-300 dark:hover:border-sky-700 transition-all">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">{{ $a->title }}</h3>
            <p class="text-xs text-zinc-500 line-clamp-2 mb-3">{{ Str::limit(strip_tags($a->content), 120) }}</p>
            <div class="flex items-center gap-2 text-xs text-zinc-400 flex-wrap">
                <span class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800">{{ $a->categoryLabel }}</span>
                @foreach($a->kbTags->take(3) as $t)<span class="text-{{ $t->color }}-500">#{{ $t->name }}</span>@endforeach
                @if($a->attachments->isNotEmpty())<span>📎{{ $a->attachments->count() }}</span>@endif
                <span class="ml-auto">{{ $a->author->name }} · {{ $a->view_count }}次</span>
            </div>
        </button>
        @endforeach
    </div>
    @endif
    @if($articles->hasPages() && !$viewArticle)<div class="flex justify-center">{{ $articles->links() }}</div>@endif
</div>
