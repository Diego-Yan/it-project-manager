<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">发布管理</h1><p class="text-sm text-zinc-500 mt-1">版本发布追踪：版本号、Git 关联、部署状态</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 新建发布</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑发布' : '新建发布' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formVersion" placeholder="版本号: v2.3.1" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formProjectId" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">选择项目*</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formServiceId" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">关联服务</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            <input wire:model="formGitRepo" placeholder="Git 仓库 URL" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <input wire:model="formGitRef" placeholder="分支/标签/commit" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formStatus" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="planned">计划中</option><option value="deploying">部署中</option><option value="success">成功</option><option value="failed">失败</option><option value="rolled_back">已回滚</option></select>
        </div>
        <textarea wire:model="formChangelog" rows="3" placeholder="变更日志" class="w-full mt-3 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        <div class="flex gap-2 justify-end mt-3">
            <button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button>
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($releases->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无发布记录</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($releases as $rel)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-sm font-mono font-medium text-zinc-900 dark:text-white">{{ $rel->version }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $rel->statusColor }}-100 dark:bg-{{ $rel->statusColor }}-950/40 text-{{ $rel->statusColor }}-700 dark:text-{{ $rel->statusColor }}-400">{{ $rel->statusLabel }}</span>
                    <span class="text-xs text-zinc-500">{{ $rel->project->title }}</span>
                    @if($rel->git_ref)<span class="text-xs text-zinc-400 font-mono">{{ $rel->git_ref }}</span>@endif
                    @if($rel->deployer)<span class="text-xs text-zinc-400">· {{ $rel->deployer->name }}</span>@endif
                    @if($rel->deployed_at)<span class="text-xs text-zinc-400">· {{ $rel->deployed_at->format('m/d H:i') }}</span>@endif
                </div>
                @if($rel->changelog)<p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $rel->changelog }}</p>@endif
                <div class="flex gap-2 mt-2">
                    @if($rel->status === 'planned')<button wire:click="markStatus({{ $rel->id }},'deploying')" class="text-xs text-sky-600 hover:underline">开始部署</button>@endif
                    @if($rel->status === 'deploying')<button wire:click="markStatus({{ $rel->id }},'success')" class="text-xs text-green-600 hover:underline">部署成功</button><button wire:click="markStatus({{ $rel->id }},'failed')" class="text-xs text-red-500 hover:underline ml-2">部署失败</button>@endif
                    @if(in_array($rel->status,['success','failed']))<button wire:click="markStatus({{ $rel->id }},'rolled_back')" class="text-xs text-amber-600 hover:underline">回滚</button>@endif
                    <button wire:click="edit({{ $rel->id }})" class="text-xs text-zinc-400 hover:text-sky-500 ml-2">编辑</button>
                    <button wire:click="delete({{ $rel->id }})" wire:confirm="删除？" class="text-xs text-zinc-400 hover:text-red-500">删除</button>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($releases->hasPages()) <div class="flex justify-center">{{ $releases->links() }}</div> @endif
</div>
