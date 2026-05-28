<div class="space-y-5">

    {{-- 顶部面包屑 --}}
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <a href="{{ route('projects.index') }}" class="hover:text-sky-600 transition-colors">项目管理</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        <span class="text-zinc-900 dark:text-white truncate">{{ $project->title }}</span>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">

        {{-- 左侧主信息 --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- 项目标题卡片 --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $project->category->colorClass }}">
                                {{ $project->category->name }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                {{ $project->typeLabel }}
                            </span>
                        </div>
                        <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $project->title }}</h1>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @can('edit projects')
                        <a href="{{ route('projects.edit', $project) }}"
                            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                            </svg>
                            编辑
                        </a>
                        @endcan
                    </div>
                </div>

                {{-- 进度条 --}}
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        @php $color = $project->progressColor @endphp
                        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-{{ $color }}-600 dark:text-{{ $color }}-400">
                            <span class="w-2 h-2 rounded-full bg-{{ $color }}-500"></span>
                            {{ $project->progressLabel }}
                        </span>
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $project->completion_percent }}%</span>
                    </div>
                    <div class="h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full bg-{{ $color }}-500 rounded-full transition-all duration-500"
                            style="width: {{ $project->completion_percent }}%"></div>
                    </div>
                </div>

                {{-- 描述 --}}
                @if($project->description)
                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ $project->description }}</p>
                @else
                <p class="text-sm text-zinc-400 italic">暂无描述</p>
                @endif

                {{-- 快捷进度切换 --}}
                <div class="mt-5 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 mb-2">快速切换进度</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['pending'=>'未开始','in_progress'=>'进行中','paused'=>'已暂停','completed'=>'已完成'] as $key => $label)
                        <button wire:click="changeProgress('{{ $key }}')"
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all
                                {{ $project->progress === $key
                                    ? 'bg-sky-600 border-sky-600 text-white'
                                    : 'border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:border-sky-400 dark:hover:border-sky-500 hover:text-sky-600 dark:hover:text-sky-400' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 操作日志 --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">操作记录</h3>
                @if($project->logs->isEmpty())
                <p class="text-center text-sm text-zinc-400 py-4">暂无操作记录</p>
                @else
                <div class="space-y-3">
                    @foreach($project->logs as $log)
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/40 flex items-center justify-center text-sky-600 dark:text-sky-400 text-xs font-semibold shrink-0">
                            {{ mb_substr($log->user?->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-800 dark:text-zinc-200">
                                <span class="font-medium">{{ $log->user?->name }}</span>
                                <span class="text-zinc-500"> {{ $log->actionLabel }}</span>
                            </p>
                            @if($log->comment)
                            <p class="text-xs text-zinc-500 mt-0.5">{{ $log->comment }}</p>
                            @endif
                            <p class="text-xs text-zinc-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- 右侧侧边栏 --}}
        <div class="space-y-5">

            {{-- 项目信息 --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">项目信息</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs text-zinc-500">创建人</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white mt-0.5">{{ $project->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500">负责人</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white mt-0.5">{{ $project->owner?->name ?? '未指定' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500">开始日期</dt>
                        <dd class="text-sm font-medium text-zinc-900 dark:text-white mt-0.5">{{ $project->start_date?->format('Y-m-d') ?? '未设置' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500">计划完成</dt>
                        <dd class="text-sm font-medium mt-0.5 {{ $project->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                            {{ $project->end_date?->format('Y-m-d') ?? '未设置' }}
                            @if($project->isOverdue()) <span class="text-xs">(已逾期)</span> @endif
                        </dd>
                    </div>
                    @if($project->remark)
                    <div>
                        <dt class="text-xs text-zinc-500">备注</dt>
                        <dd class="text-sm text-zinc-700 dark:text-zinc-300 mt-0.5">{{ $project->remark }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- 项目成员 --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">项目成员</h3>
                    @can('assign project members')
                    <button @click="$wire.showMemberModal = true"
                        class="text-xs text-sky-600 dark:text-sky-400 hover:underline">添加</button>
                    @endcan
                </div>

                <div class="space-y-2">
                    @forelse($project->members as $member)
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-{{ ['sky','violet','green','amber','red'][($member->id % 5)] }}-100 dark:bg-{{ ['sky','violet','green','amber','red'][($member->id % 5)] }}-950/40 flex items-center justify-center text-{{ ['sky','violet','green','amber','red'][($member->id % 5)] }}-600 text-xs font-semibold">
                            {{ mb_substr($member->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $member->name }}</p>
                            <p class="text-xs text-zinc-500">{{ $member->pivot->role === 'lead' ? '负责人' : '成员' }}</p>
                        </div>
                        @can('assign project members')
                        @if($member->pivot->role !== 'lead')
                        <button wire:click="removeMember({{ $member->id }})"
                            class="text-zinc-400 hover:text-red-500 transition-colors p-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        @endif
                        @endcan
                    </div>
                    @empty
                    <p class="text-xs text-zinc-400">暂无成员</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- 添加成员模态框 --}}
    @if($showMemberModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        x-data x-on:click.self="$wire.showMemberModal = false">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">添加项目成员</h3>
            <input type="text" wire:model="newMemberUsername" placeholder="输入用户名或姓名"
                class="w-full px-4 py-2.5 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 mb-2">
            @error('newMemberUsername') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror
            <div class="flex gap-2 justify-end">
                <button wire:click="$set('showMemberModal', false)"
                    class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">取消</button>
                <button wire:click="addMember"
                    class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-colors">确认添加</button>
            </div>
        </div>
    </div>
    @endif

</div>
