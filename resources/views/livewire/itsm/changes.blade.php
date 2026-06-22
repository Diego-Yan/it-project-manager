<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ __('变更管理') }}</h1><p class="text-sm text-zinc-500 mt-1">{{ __('ITIL 变更请求：发布、配置、回滚、紧急修复') }}</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">{{ __('+ 新建变更') }}</button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? __('编辑变更') : __('新建变更请求') }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <input wire:model="formTitle" placeholder="{{ __('变更标题') }}" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-3">
            <select wire:model="formProjectId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">{{ __('选择项目*') }}</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formServiceId" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">{{ __('关联服务') }}</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            <select wire:model="formType" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="release">{{ __('发布') }}</option><option value="config">{{ __('配置变更') }}</option><option value="rollback">{{ __('回滚') }}</option><option value="hotfix">{{ __('紧急修复') }}</option></select>
            <select wire:model="formRisk" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="low">{{ __('风险：低') }}</option><option value="medium">{{ __('风险：中') }}</option><option value="high">{{ __('风险：高') }}</option><option value="critical">{{ __('风险：严重') }}</option></select>
            <input type="datetime-local" wire:model="formWindowStart" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" placeholder="{{ __('变更窗口开始') }}">
            <input type="datetime-local" wire:model="formWindowEnd" class="px-3 h-10 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700" placeholder="{{ __('变更窗口结束') }}">
        </div>
        <textarea wire:model="formDescription" rows="2" placeholder="{{ __('变更描述') }}" class="w-full mt-3 px-3 min-h-[5rem] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formTitle')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <textarea wire:model="formRollbackPlan" rows="2" placeholder="{{ __('回滚方案') }}" class="w-full mt-3 px-3 min-h-[5rem] text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        @error('formTitle')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <div class="flex gap-2 justify-end mt-3">
            <button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">{{ __('取消') }}</button>
            <button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">{{ __('保存为草稿') }}</button>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        @if($changes->isEmpty())
        <div class="text-center py-16 text-zinc-400 text-sm">{{ __('暂无变更记录') }}</div>
        @else
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach($changes as $cr)
            <div class="px-5 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/30">
                <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $cr->title }}</span>
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-{{ $cr->riskColor }}-100 dark:bg-{{ $cr->riskColor }}-950/40 text-{{ $cr->riskColor }}-700 dark:text-{{ $cr->riskColor }}-400">{{ $cr->riskLabel }}{{ __('风险') }}</span>
                    <span class="text-xs text-zinc-500">{{ $cr->typeLabel }}</span>
                    <span class="text-xs text-zinc-500">· {{ $cr->project->title }}</span>
                    @if($cr->service)<span class="text-xs text-zinc-500">· {{ $cr->service->name }}</span>@endif
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs text-zinc-400">{{ $cr->statusLabel }} · {{ $cr->requester->name }}</span>
                    {{-- Action buttons --}}
                    @if($cr->status === 'draft')
                    <button wire:click="submitForApproval({{ $cr->id }})" class="text-xs text-amber-600 hover:underline ml-2">{{ __('提交审批') }}</button>
                    @endif
                    @if($cr->status === 'pending_approval')
                    <button wire:click="approve({{ $cr->id }})" class="text-xs text-green-600 hover:underline ml-2">{{ __('批准') }}</button>
                    <button wire:click="reject({{ $cr->id }})" class="text-xs text-red-500 hover:underline">{{ __('拒绝') }}</button>
                    @endif
                    @if($cr->status === 'approved')
                    <button wire:click="startImplement({{ $cr->id }})" class="text-xs text-sky-600 hover:underline ml-2">{{ __('开始执行') }}</button>
                    @endif
                    @if($cr->status === 'in_progress')
                    <button wire:click="complete({{ $cr->id }})" class="text-xs text-green-600 hover:underline ml-2">{{ __('完成') }}</button>
                    <button wire:click="rollback({{ $cr->id }})" class="text-xs text-red-500 hover:underline">{{ __('回滚') }}</button>
                    @endif
                    @if(in_array($cr->status, ['draft','rejected']))
                    <button wire:click="edit({{ $cr->id }})" class="text-xs text-zinc-400 hover:text-sky-500 ml-2">{{ __('编辑') }}</button>
                    <button wire:click="delete({{ $cr->id }})" wire:confirm="{{ __('删除？') }}" class="text-xs text-zinc-400 hover:text-red-500">{{ __('删除') }}</button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @if($changes->hasPages()) <div class="flex justify-center">{{ $changes->links() }}</div> @endif
</div>
