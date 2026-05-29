<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">CI/CD 流水线</h1><p class="text-sm text-zinc-500 mt-1">构建 → 测试 → 部署 端到端流水线追踪</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 新建流水线</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑流水线' : '新建流水线' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formName" placeholder="流水线名称" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
            <select wire:model="formProjectId" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">选择项目*</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formTrigger" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="push">Push 触发</option><option value="tag">Tag 触发</option><option value="manual">手动触发</option><option value="schedule">定时触发</option></select>
            <input wire:model="formGitRepo" placeholder="Git 仓库 URL" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-2">
        </div>
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button></div>
    </div>
    @endif

    @foreach($pipelines as $pipeline)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $pipeline->name }}</span>
                <span class="text-xs text-zinc-500 ml-2">{{ $pipeline->project->title }}</span>
                <span class="text-xs text-zinc-400 ml-2">{{ $pipeline->git_repo ?: '—' }}</span>
            </div>
            <div class="flex gap-2">
                <button wire:click="runPipeline({{ $pipeline->id }})" class="px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg">▶ 运行</button>
                <button wire:click="edit({{ $pipeline->id }})" class="p-1.5 text-zinc-400 hover:text-sky-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                <button wire:click="delete({{ $pipeline->id }})" wire:confirm="删除？" class="p-1.5 text-zinc-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>

        {{-- 最近的运行 --}}
        @php $recentRuns = $pipeline->runs->take(5); @endphp
        @if($recentRuns->isNotEmpty())
        <div class="space-y-1">
            @foreach($recentRuns as $run)
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 text-xs">
                <span class="w-2 h-2 rounded-full bg-{{ $run->statusColor }}-500"></span>
                <span class="text-zinc-600 dark:text-zinc-400 font-mono">{{ $run->commit_sha ? substr($run->commit_sha,0,7) : '#' . $run->id }}</span>
                <span class="text-zinc-500">{{ $run->status }}</span>
                @if($run->duration)<span class="text-zinc-400">{{ $run->duration }}</span>@endif
                <span class="text-zinc-400 ml-auto">{{ $run->created_at->format('m/d H:i') }}</span>
                <button wire:click="toggleRun({{ $run->id }})" class="text-sky-500 hover:underline">详情</button>
            </div>
            @endforeach
        </div>
        @endif

        {{-- 运行详情：阶段 --}}
        @if($runDetails && $runDetails->pipeline_id === $pipeline->id)
        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                @foreach($runDetails->stages as $stage)
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-{{ $stage->statusColor }}-100 dark:bg-{{ $stage->statusColor }}-950/40 text-{{ $stage->statusColor }}-700 dark:text-{{ $stage->statusColor }}-400">
                    {{ $stage->stageIcon }} {{ $stage->name }}
                    @if($stage->duration_seconds)<span class="opacity-70">{{ $stage->duration_seconds }}s</span>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endforeach

    @if($pipelines->hasPages()) <div class="flex justify-center">{{ $pipelines->links() }}</div> @endif
</div>
