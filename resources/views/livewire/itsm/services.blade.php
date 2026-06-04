<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">服务目录</h1><p class="text-sm text-zinc-500 mt-1">管理 IT 服务及其健康状态</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 添加服务</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑服务' : '添加服务' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formName" placeholder="服务名称" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formProjectId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">关联项目（可选）</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formType" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="web">🌐 Web</option><option value="database">🗄️ 数据库</option><option value="cache">⚡ 缓存</option><option value="queue">📨 队列</option><option value="storage">💾 存储</option><option value="api">🔌 API</option><option value="custom">🔧 其他</option></select>
            <select wire:model="formStatus" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="healthy">🟢 健康</option><option value="degraded">🟡 降级</option><option value="down">🔴 宕机</option><option value="maintenance">⚙️ 维护中</option></select>
            <select wire:model="formOwnerId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">负责人</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <input wire:model="formHealthUrl" placeholder="健康检查 URL" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
        </div>
        <textarea wire:model="formDescription" rows="2" placeholder="描述" class="w-full mt-3 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <input wire:model="formTags" placeholder="标签，逗号分隔: production, k8s, beijing" class="w-full mt-3 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
        <div class="flex gap-2 justify-end mt-3">
            <button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button>
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($services->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无服务</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($services as $s)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <span class="text-lg shrink-0">{{ $s->typeIcon }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $s->name }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $s->statusColor }}-100 dark:bg-{{ $s->statusColor }}-950/40 text-{{ $s->statusColor }}-700 dark:text-{{ $s->statusColor }}-400">{{ $s->statusLabel }}</span>
                        @if($s->project)<span class="text-xs text-zinc-500">{{ $s->project->title }}</span>@endif
                    </div>
                    @if($s->tags)<div class="flex gap-1 mt-1">@foreach($s->tags as $t)<span class="text-xs text-zinc-400">#{{ $t }}</span>@endforeach</div>@endif
                </div>
                <div class="flex gap-1 shrink-0">
                    <button wire:click="edit({{ $s->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                    <button wire:click="delete({{ $s->id }})" wire:confirm="确定删除？" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($services->hasPages()) <div class="flex justify-center">{{ $services->links() }}</div> @endif
</div>
