<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-zinc-900 dark:text-white">环境管理</h1><p class="text-sm text-zinc-500 mt-1">多环境部署管理：开发 / 预发布 / 生产</p></div>
        <button wire:click="$set('showForm', true)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-sm font-medium rounded-xl">+ 添加环境</button>
    </div>

    @if(session('success'))<div class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-400 text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-400 text-sm">{{ session('error') }}</div>@endif

    @if($showForm)
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border p-5">
        <h3 class="text-sm font-semibold mb-4">{{ $editingId ? '编辑环境' : '添加环境' }}</h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <select wire:model="formProjectId" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="">选择项目*</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
            <select wire:model="formName" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="dev">开发 (dev)</option><option value="staging">预发布 (staging)</option><option value="prod">生产 (prod)</option></select>
            <select wire:model="formType" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"><option value="server">服务器</option><option value="k8s">Kubernetes</option><option value="serverless">Serverless</option></select>
            <input wire:model="formHostUrl" placeholder="环境 URL" class="px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 sm:col-span-2">
        </div>
        <textarea wire:model="formDescription" rows="2" placeholder="描述" class="w-full mt-3 px-3 py-2 text-sm border rounded-xl dark:bg-zinc-800 dark:border-zinc-700"></textarea>
        <div class="flex gap-2 justify-end mt-3"><button wire:click="resetForm" class="px-4 py-2 text-sm text-zinc-500">取消</button><button wire:click="save" class="px-4 py-2 text-sm font-medium bg-sky-600 text-white rounded-xl">保存</button></div>
    </div>
    @endif

    <div class="grid md:grid-cols-3 gap-4">
        @foreach($environments as $env)
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg text-xs font-bold bg-{{ $env->envColor }}-100 dark:bg-{{ $env->envColor }}-950/40 text-{{ $env->envColor }}-700 dark:text-{{ $env->envColor }}-400">{{ $env->typeLabel }}</span>
                <div class="flex gap-1">
                    <button wire:click="edit({{ $env->id }})" class="p-1 text-zinc-400 hover:text-sky-500"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                </div>
            </div>
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $env->project->title }} / {{ $env->name }}</p>
            @if($env->host_url)<p class="text-xs text-zinc-500 mt-1 truncate">{{ $env->host_url }}</p>@endif
            @if($env->latestDeployment)
            <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <span class="text-xs text-zinc-500">当前版本</span>
                <span class="ml-2 text-xs font-mono font-medium text-zinc-900 dark:text-white">{{ $env->latestDeployment->version }}</span>
                <span class="text-xs text-zinc-400 ml-2">{{ $env->latestDeployment->created_at->format('m/d H:i') }}</span>
            </div>
            @endif
            <div class="flex gap-2 mt-3">
                <button wire:click="deploy({{ $env->id }})" class="flex-1 px-3 py-1.5 text-xs font-medium bg-sky-600 text-white rounded-lg">部署</button>
                <button wire:click="rollback({{ $env->id }})" class="flex-1 px-3 py-1.5 text-xs font-medium bg-amber-600 text-white rounded-lg">回滚</button>
            </div>
        </div>
        @endforeach
    </div>
    @if($environments->hasPages()) <div class="flex justify-center">{{ $environments->links() }}</div> @endif
</div>
