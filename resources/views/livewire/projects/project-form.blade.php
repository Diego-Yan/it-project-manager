<div class="max-w-3xl mx-auto space-y-6">

    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-6">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-6">
            {{ $isEdit ? __('编辑项目') : __('创建新项目') }}
        </h2>

        <form wire:submit="save" class="space-y-5">

            {{-- 项目标题 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('项目标题') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="title" placeholder="{{ __('请输入项目标题') }}"
                    class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-colors">
                @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- 分类 + 地区 + 类型 --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('项目分类') }} <span class="text-red-500">*</span></label>
                    <select wire:model="category_id"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="">{{ __('请选择分类') }}</option>
                        @php $opsCategories = $categories->where('type','ops'); $devCategories = $categories->where('type','dev'); @endphp
                        @if($opsCategories->count())
                        <optgroup label="{{ __('── 运维项目 ──') }}">
                            @foreach($opsCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                        @endif
                        @if($devCategories->count())
                        <optgroup label="{{ __('── 开发项目 ──') }}">
                            @foreach($devCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </optgroup>
                        @endif
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('地区') }} <span class="text-red-500">*</span></label>
                    <select wire:model="region_id"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="">{{ __('请选择地区') }}</option>
                        @foreach($regions as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('项目类型') }}</label>
                    <select wire:model="type"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="new">{{ __('新增') }}</option>
                        <option value="improved">{{ __('改善') }}</option>
                        <option value="issue">{{ __('异常') }}</option>
                    </select>
                </div>
            </div>

            {{-- 紧急度 + 重要性 --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('紧急度') }}</label>
                    <select wire:model="urgency"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="not_urgent">{{ __('不紧急') }}</option>
                        <option value="normal">{{ __('一般') }}</option>
                        <option value="urgent">{{ __('紧急') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('重要性') }}</label>
                    <select wire:model="importance"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="normal">{{ __('一般') }}</option>
                        <option value="important">{{ __('重要') }}</option>
                        <option value="very_important">{{ __('非常重要') }}</option>
                    </select>
                </div>
            </div>

            {{-- 进度 + 完成百分比 --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('当前进度') }}</label>
                    <select wire:model="progress"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                        <option value="pending">{{ __('未开始') }}</option>
                        <option value="in_progress">{{ __('进行中') }}</option>
                        <option value="paused">{{ __('已暂停') }}</option>
                        <option value="completed">{{ __('已完成') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('完成百分比') }}：{{ $completion_percent }}%</label>
                    <input type="range" wire:model.live="completion_percent" min="0" max="100" step="5"
                        class="w-full h-2 bg-zinc-200 dark:bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-sky-500">
                </div>
            </div>

            {{-- 负责人 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('项目负责人') }}</label>
                <select wire:model="owner_id"
                    class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                    <option value="">{{ __('无（未指定）') }}</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} {{ $u->department ? '('.$u->department.')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 日期 --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('开始日期') }}</label>
                    <input type="date" wire:model="start_date"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('计划完成日期') }}</label>
                    <input type="date" wire:model="end_date"
                        class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:border-sky-500 transition-colors">
                    @error('end_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- 描述 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('项目描述') }}</label>
                <textarea wire:model="description" rows="5" placeholder="{{ __('请描述项目背景、目标和范围...') }}"
                    x-data x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    class="w-full px-4 py-3 min-h-[120px] text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors resize-none"></textarea>
            </div>

            {{-- 备注 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">{{ __('备注') }}</label>
                <input type="text" wire:model="remark" placeholder="{{ __('简短备注（可选）') }}"
                    class="w-full px-4 h-10 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500 transition-colors">
            </div>

            {{-- 任务清单（仅新建时显示） --}}
            @if(!$isEdit)
            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                    {{ __('初始任务') }}
                    <span class="text-xs text-zinc-400 font-normal">{{ __('（可先添加几个任务，项目创建后还能继续添加）') }}</span>
                </label>

                {{-- 已添加的任务 --}}
                @if($inlineTasks)
                <div class="space-y-2 mb-3">
                    @foreach($inlineTasks as $i => $task)
                    <div class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <span class="flex-1 text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ $task['title'] }}</span>
                        <span class="text-xs text-zinc-400 shrink-0">
                            @if($task['priority'] === 'urgent') <span class="text-red-500">{{ __('紧急') }}</span>
                            @elseif($task['priority'] === 'not_urgent') {{ __('不紧急') }}
                            @else {{ __('一般') }} @endif
                        </span>
                        <button wire:click="removeInlineTask({{ $i }})"
                            class="text-zinc-400 hover:text-red-500 p-1 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- 添加任务输入 --}}
                <div class="flex gap-2">
                    <input type="text" wire:model="newTaskTitle" placeholder="{{ __('任务标题') }}"
                        wire:keydown.enter="addInlineTask"
                        class="flex-1 px-3 h-10 text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:border-sky-500">
                    <select wire:model="newTaskPriority"
                        class="text-sm bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg px-2 py-2 text-zinc-700 dark:text-zinc-300 shrink-0">
                        <option value="not_urgent">{{ __('不紧急') }}</option>
                        <option value="normal">{{ __('一般') }}</option>
                        <option value="urgent">{{ __('紧急') }}</option>
                    </select>
                    <button type="button" wire:click="addInlineTask"
                        class="px-3 py-2 text-xs font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-lg transition-colors shrink-0">
                        {{ __('添加') }}
                    </button>
                </div>
            </div>
            @endif

            {{-- 按钮 --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ $isEdit ? route('projects.show', $project) : route('projects.index') }}"
                    class="px-5 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-xl transition-colors">
                    {{ __('取消') }}
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-70"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-sky-600 hover:bg-sky-500 rounded-xl transition-all hover:shadow-lg hover:shadow-sky-500/20 disabled:cursor-not-allowed">
                    <span wire:loading.remove>{{ $isEdit ? __('保存修改') : __('创建项目') }}</span>
                    <span wire:loading>{{ __('保存中...') }}</span>
                </button>
            </div>

        </form>
    </div>
</div>
