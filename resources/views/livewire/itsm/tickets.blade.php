<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">工单管理</h1><p class="text-sm text-zinc-500 mt-1">故障报修 · 服务请求 · 变更工单</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 新建工单</button>
    </div>

    @if($openCount > 0)
    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-400">
        {{ $openCount }} 个工单待处理
    </div>
    @endif

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑工单' : '新建工单' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formTitle" placeholder="工单标题*" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-3">
            <select wire:model="formType" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="request">服务请求</option><option value="incident">故障报修</option><option value="change">变更申请</option><option value="problem">问题管理</option></select>
            <select wire:model="formPriority" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="low">优先级：低</option><option value="medium">优先级：中</option><option value="high">优先级：高</option><option value="critical">优先级：紧急</option></select>
            <select wire:model="formSource" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="phone">电话</option><option value="email">邮件</option><option value="portal">自助</option><option value="walk_in">现场</option></select>
            <select wire:model="formProjectId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">关联项目</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formRegionId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">请选择地区 *</option>@foreach($regions as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
            <select wire:model="formAssetId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">关联资产</option>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ $a->asset_tag }})</option>@endforeach</select>
            <select wire:model="formAssignedTo" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">分配处理人</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
        </div>
        <textarea wire:model="formDescription" rows="2" placeholder="详细描述" class="w-full mt-3 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formTitle')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror
        @error('formRegionId')<p class="text-xs text-red-500 mb-2">请选择地区</p>@enderror
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button></div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border overflow-hidden">
        @if($tickets->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">暂无工单 ✓</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($tickets as $ticket)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30 {{ $ticket->isSlaBreached() ? 'border-l-4 border-l-red-500' : '' }}">
                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->typeColor }}-100 dark:bg-{{ $ticket->typeColor }}-950/40 text-{{ $ticket->typeColor }}-700 dark:text-{{ $ticket->typeColor }}-400">{{ $ticket->typeLabel }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $ticket->priorityColor }}-100 dark:bg-{{ $ticket->priorityColor }}-950/40 text-{{ $ticket->priorityColor }}-700 dark:text-{{ $ticket->priorityColor }}-400">{{ $ticket->priorityLabel }}优先</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $ticket->title }}</span>
                    @if($ticket->isSlaBreached())<span class="text-xs text-red-500 font-bold">SLA 已超时</span>@endif
                </div>
                <div class="flex items-center gap-3 text-xs text-zinc-500 flex-wrap">
                    <span>状态: {{ $ticket->statusLabel }}</span>
                    @if($ticket->assignee)<span>· {{ $ticket->assignee->name }}</span>@else<span>· 未分配</span>@endif
                    @if($ticket->region)<span>· {{ $ticket->region->name }}</span>@endif
                    @if($ticket->asset)<span>· {{ $ticket->asset->name }}</span>@endif
                    @if($ticket->project)<span>· {{ $ticket->project->title }}</span>@endif
                    <span>· 来源: {{ $ticket->sourceLabel }}</span>
                    <span>· {{ $ticket->created_at->diffForHumans() }}</span>

                    {{-- 操作按钮 --}}
                    <div class="flex items-center gap-1.5 flex-wrap mt-1 sm:mt-0">
                    @if($ticket->status === 'open')
                    <button wire:click="assign({{ $ticket->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-lg min-w-[44px]">接单</button>
                    @can('manage tickets')
                    <select wire:model="assignToUserId" class="text-xs border rounded-lg px-2 py-1.5 dark:bg-zinc-800 dark:border-zinc-700 min-w-[80px]">
                        <option value="">分配...</option>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    <button wire:click="assignTo({{ $ticket->id }})" class="px-2 py-1.5 text-xs text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-950/30 rounded-lg">分配</button>
                    @endcan
                    @endif
                    @if($ticket->status === 'in_progress' && $ticket->assigned_to === auth()->id())
                    <button wire:click="resolve({{ $ticket->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-500 rounded-lg min-w-[44px]">解决</button>
                    @endif
                    @if($ticket->status === 'resolved')
                    <button wire:click="close({{ $ticket->id }})" class="px-3 py-1.5 text-xs font-medium text-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg min-w-[44px]">关闭</button>
                    @endif
                    <button wire:click="toggleView({{ $ticket->id }})" class="px-2 py-1.5 text-xs text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg min-w-[36px]">详情</button>
                    <button wire:click="edit({{ $ticket->id }})" class="px-2 py-1.5 text-xs text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg min-w-[36px]">编辑</button>
                    </div>
                </div>

                {{-- 详情展开 --}}
                @if($viewTicketId === $ticket->id)
                <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 space-y-2">
                    @if($ticket->description)<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ticket->description }}</p>@endif
                    @foreach($ticket->comments as $c)
                    <div class="flex gap-2 text-xs"><span class="font-medium text-zinc-600 dark:text-zinc-400">{{ $c->user->name }}</span><span class="text-zinc-500">{{ $c->content }}</span><span class="text-zinc-400">{{ $c->created_at->diffForHumans() }}</span></div>
                    @endforeach
                    <div class="flex gap-2"><input wire:model="newComment" wire:keydown.enter="addComment({{ $ticket->id }})" placeholder="添加处理记录..." class="flex-1 px-2 py-1 text-xs border rounded dark:bg-zinc-800 dark:border-zinc-700"><button wire:click="addComment({{ $ticket->id }})" class="px-2 py-1 text-xs bg-sky-600 text-white rounded">发送</button></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($tickets->hasPages())<div class="flex justify-center">{{ $tickets->links() }}</div>@endif
</div>
