<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">故障管理</h1><p class="text-sm text-zinc-500 mt-1">故障响应：P0-P4 等级、时间线追踪、事后复盘</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-medium rounded-xl">+ 上报故障</button>
    </div>

    @if($openCount > 0)
    <div class="p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-400">
        ⚠️ 当前有 <strong>{{ $openCount }}</strong> 个未关闭的故障
    </div>
    @endif

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑故障' : '上报新故障' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formTitle" placeholder="故障标题" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-2">
            <select wire:model="formSeverity" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="P0">P0 紧急</option><option value="P1">P1 严重</option><option value="P2">P2 一般</option><option value="P3">P3 轻微</option><option value="P4">P4 建议</option></select>
            <select wire:model="formProjectId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">选择项目*</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formServiceId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">关联服务</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            <select wire:model="formAssignedTo" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">分配处理人</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        </div>
        <textarea wire:model="formDescription" rows="3" placeholder="故障描述" class="w-full mt-3 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formTitle')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <div class="flex gap-2 justify-end mt-3">
            <button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button>
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-xl">创建故障</button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($incidents->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无故障记录 ✓</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($incidents as $inc)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <div class="flex items-center gap-3 flex-wrap mb-1.5">
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-{{ $inc->severityColor }}-100 dark:bg-{{ $inc->severityColor }}-950/40 text-{{ $inc->severityColor }}-700 dark:text-{{ $inc->severityColor }}-400">{{ $inc->severityLabel }}</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $inc->title }}</span>
                    <span class="text-xs text-zinc-500">{{ $inc->project->title }}</span>
                    @if($inc->service)<span class="text-xs text-zinc-500">· {{ $inc->service->name }}</span>@endif
                </div>
                <div class="flex items-center gap-2 text-xs text-zinc-400 flex-wrap">
                    <span>状态: {{ $inc->statusLabel }}</span>
                    @if($inc->assignee)<span>· 处理人: {{ $inc->assignee->name }}</span>@endif
                    @if($inc->mttr)<span class="text-zinc-500 font-medium">· MTTR: {{ $inc->mttr }}</span>@endif
                    <span>· {{ $inc->created_at->diffForHumans() }}</span>

                    @if($inc->status !== 'closed' && $inc->status !== 'resolved')
                    <div class="flex gap-1 ml-2">
                        @if($inc->status === 'open')<button wire:click="addTimeline({{ $inc->id }},'investigating')" class="text-xs text-sky-600 hover:underline">开始调查</button>@endif
                        @if(in_array($inc->status,['open','investigating']))<button wire:click="addTimeline({{ $inc->id }},'mitigated')" class="text-xs text-amber-600 hover:underline ml-1">已缓解</button>@endif
                        @if(in_array($inc->status,['open','investigating','mitigated']))<button wire:click="addTimeline({{ $inc->id }},'resolved')" class="text-xs text-green-600 hover:underline ml-1">已解决</button>@endif
                    </div>
                    @endif
                    <button wire:click="close({{ $inc->id }})" wire:confirm="确定关闭此故障？" class="text-xs text-zinc-400 hover:text-red-500 ml-2">关闭</button>
                    <button wire:click="toggleTimeline({{ $inc->id }})" class="text-xs text-zinc-400 hover:text-sky-500 ml-1">时间线</button>
                    <button wire:click="edit({{ $inc->id }})" class="text-xs text-zinc-400 hover:text-sky-500 ml-1">编辑</button>
                </div>

                {{-- 时间线 --}}
                @if($viewTimelineId === $inc->id && isset($timelines[$inc->id]))
                <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 ml-6 space-y-2">
                    @foreach($timelines[$inc->id] as $t)
                    <div class="flex gap-2 text-xs">
                        <span class="text-zinc-400 shrink-0 w-20">{{ $t->created_at->format('m/d H:i') }}</span>
                        <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ $t->user->name }}</span>
                        <span class="text-zinc-500">{{ $t->action === 'created' ? '创建' : ($t->action === 'investigating' ? '开始调查' : ($t->action === 'mitigated' ? '缓解' : ($t->action === 'resolved' ? '解决' : ($t->action === 'closed' ? '关闭' : $t->action)))) }}</span>
                        @if($t->description)<span class="text-zinc-500">— {{ $t->description }}</span>@endif
                    </div>
                    @endforeach
                    <div class="flex gap-2 mt-2">
                        <input wire:model="timelineNote" placeholder="添加备注..." wire:keydown.enter="addTimeline({{ $inc->id }},'commented')" class="flex-1 px-2 py-1 text-xs border rounded dark:bg-zinc-800 dark:border-zinc-700">
                        <button wire:click="addTimeline({{ $inc->id }},'commented')" class="text-xs px-2 py-1 bg-sky-600 text-white rounded">添加</button>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($incidents->hasPages()) <div class="flex justify-center">{{ $incidents->links() }}</div> @endif
</div>
