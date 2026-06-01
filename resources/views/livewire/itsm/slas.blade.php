<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">SLA 管理</h1><p class="text-sm text-zinc-500 mt-1">服务等级协议：响应/解决时限配置</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 添加 SLA</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑 SLA' : '添加 SLA' }}</h3>
        <div class="grid sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">SLA 名称</label>
                <input wire:model="formName" placeholder="如：紧急故障处理" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                @error('formName')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">优先级</label>
                <select wire:model="formPriority" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="low">低</option><option value="medium">中</option><option value="high">高</option><option value="critical">紧急</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">响应时限（分钟）</label>
                <input type="number" wire:model="formResponse" min="1" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                @error('formResponse')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">解决时限（分钟）</label>
                <input type="number" wire:model="formResolution" min="1" class="w-full px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700">
                @error('formResolution')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <label class="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formIsActive" class="rounded"> 启用</label>
        <div class="flex gap-2 justify-end mt-4"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button></div>
    </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4">
        @foreach($slas as $sla)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5 {{ $sla->is_active ? '' : 'opacity-50' }}">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $sla->name }}</span>
                <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-{{ $sla->priority === 'critical' ? 'red' : ($sla->priority === 'high' ? 'amber' : ($sla->priority === 'medium' ? 'sky' : 'zinc')) }}-100 dark:bg-{{ $sla->priority === 'critical' ? 'red' : ($sla->priority === 'high' ? 'amber' : ($sla->priority === 'medium' ? 'sky' : 'zinc')) }}-950/40 text-{{ $sla->priority === 'critical' ? 'red' : ($sla->priority === 'high' ? 'amber' : ($sla->priority === 'medium' ? 'sky' : 'zinc')) }}-700 dark:text-{{ $sla->priority === 'critical' ? 'red' : ($sla->priority === 'high' ? 'amber' : ($sla->priority === 'medium' ? 'sky' : 'zinc')) }}-400">{{ $sla->priority }}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-zinc-500 text-xs">响应时限</span><p class="font-medium text-zinc-900 dark:text-white">{{ $sla->response_minutes }} 分钟</p></div>
                <div><span class="text-zinc-500 text-xs">解决时限</span><p class="font-medium text-zinc-900 dark:text-white">{{ $sla->resolution_minutes }} 分钟 ({{ round($sla->resolution_minutes/60,1) }}h)</p></div>
            </div>
            <div class="flex gap-2 mt-3">
                <button wire:click="edit({{ $sla->id }})" class="text-xs text-sky-500 hover:underline">编辑</button>
                <button wire:click="delete({{ $sla->id }})" wire:confirm="删除？" class="text-xs text-red-500 hover:underline">删除</button>
            </div>
        </div>
        @endforeach
    </div>
</div>
