<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">工单管理</h1><p class="text-sm text-zinc-500 mt-1">故障报修 · 服务请求 · 变更工单</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 新建工单</button>
    </div>

    @if(session('ticket_msg'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('ticket_msg') }}</div>@endif
    @if(session('ticket_error'))<div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">{{ session('ticket_error') }}</div>@endif

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
            <div>
                <label class="text-xs text-zinc-500">类型 <span class="text-red-500">*</span></label>
                <select wire:model="formType" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="request">服务请求 — 需要 IT 提供某样东西</option>
                    <option value="incident">故障报修 — 东西坏了要修</option>
                    <option value="change">变更申请 — 计划性改动需审批</option>
                    <option value="problem">问题管理 — 查根因防复发</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">优先级</label>
                <select wire:model="formPriority" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="low">低</option><option value="medium">中</option><option value="high">高</option><option value="critical">紧急</option></select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">期望处理方式</label>
                <select wire:model="formSource" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="phone">电话远程</option><option value="walk_in">现场处理</option><option value="email">邮件沟通</option><option value="portal">自助报修</option></select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">系统分类 <span class="text-red-500">*</span></label>
                <select wire:model.live="formCategoryId" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">请选择系统</option>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select>
                @error('formCategoryId')<p class="text-xs text-red-500 mt-1">请选择系统分类</p>@enderror
            </div>
            <div>
                <label class="text-xs text-zinc-500">关联项目</label>
                <select wire:model="formProjectId" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">可选</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">地区 <span class="text-red-500">*</span></label>
                <select wire:model="formRegionId" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">请选择</option>@foreach($regions as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
            </div>
            <div>
                <label class="text-xs text-zinc-500">关联资产</label>
                <select wire:model="formAssetId" class="w-full mt-1 px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">可选</option>@foreach($assets as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ $a->asset_tag }})</option>@endforeach</select>
            </div>
            {{-- 代填 --}}
            <div class="sm:col-span-3 flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" wire:model.live="formIsProxy" class="rounded text-sky-600"> 代他人提交工单
                </label>
                @if($formIsProxy)
                <select wire:model="formReportedFor" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">选择实际报修人 *</option>
                    @foreach($users as $u)
                    @if($u->id != auth()->id())
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endif
                    @endforeach
                </select>
                @endif
            </div>

            @if($suggestedEngineers)
            <div class="sm:col-span-3 flex items-center gap-2 flex-wrap text-xs p-2 bg-sky-50 dark:bg-sky-950/20 rounded-lg">
                <span class="font-medium text-sky-700 dark:text-sky-400">💡 推荐处理人：</span>
                @foreach($suggestedEngineers as $eng)
                <button wire:click="$set('formAssignedTo', '{{ $eng['id'] }}')"
                    class="px-2 py-0.5 rounded bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-400 hover:bg-sky-200">{{ $eng['name'] }}</button>
                @endforeach
            </div>
            @endif
        </div>
        <textarea wire:model="formDescription" rows="3" placeholder="详细描述"
            x-data x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            class="w-full mt-3 px-3 py-2 min-h-[80px] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 resize-none"></textarea>
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
                    @if($ticket->status === 'in_progress' && ($ticket->assigned_to === auth()->id() || auth()->user()->can('manage tickets')))  {{-- [REVIEW-FIX] SP11.1: 管理员也应看到解决按钮（后端 SP4.1 已允许） --}}
                    <button wire:click="resolve({{ $ticket->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-500 rounded-lg min-w-[44px]">解决</button>
                    <select wire:model="assignToUserId" class="text-xs border rounded-lg px-2 py-1.5 dark:bg-zinc-800 dark:border-zinc-700 min-w-[80px]">
                        <option value="">转让...</option>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                    <button wire:click="transfer({{ $ticket->id }})" class="px-2 py-1.5 text-xs text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30 rounded-lg">转让</button>
                    @endif
                    @if($ticket->status === 'resolved')
                    <button wire:click="confirmClose({{ $ticket->id }})" class="px-3 py-1.5 text-xs font-medium text-zinc-700 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg min-w-[44px]">关闭</button>
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

    {{-- 关闭确认弹窗 --}}
    @if($showCloseConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" wire:click="$set('showCloseConfirm', false)">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-6 w-full max-w-md shadow-2xl" wire:click.stop="">
            <h3 class="text-base font-semibold mb-2">关闭工单确认</h3>
            <p class="text-sm text-zinc-500 mb-4">请填写处理过程总结，确认后工单将关闭。</p>
            <textarea wire:model="closeNote" rows="4" placeholder="处理过程总结（必填）*"
                class="w-full px-3 py-2 min-h-[100px] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 mb-2 resize-none"></textarea>
            @if(session('ticket_error'))<p class="text-xs text-red-500 mb-2">{{ session('ticket_error') }}</p>@endif
            <div class="flex gap-2 justify-end">
                <button wire:click="$set('showCloseConfirm', false)" class="px-4 py-2 text-sm text-zinc-500">取消</button>
                <button wire:click="close" class="px-4 py-2 text-sm font-medium bg-red-600 text-white rounded-xl">确认关闭</button>
            </div>
        </div>
    </div>
    @endif
</div>
