<div>
    {{-- Floating button --}}
    <button wire:click="toggle"
        class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-2xl shadow-2xl flex items-center justify-center transition-all
            {{ $isOpen ? 'bg-zinc-700 dark:bg-zinc-600 rotate-90 scale-90' : 'bg-sky-600 hover:bg-sky-500 hover:scale-105' }}">
        @if($isOpen)
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        @else
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
        @endif
    </button>

    {{-- Chat panel --}}
    @if($isOpen)
    <div class="fixed bottom-24 right-6 z-50 w-96 max-w-[calc(100vw-3rem)] h-[500px] max-h-[calc(100vh-8rem)] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800 flex flex-col overflow-hidden">
        {{-- Header --}}
        <div class="shrink-0 px-5 py-3 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-3 bg-sky-50/50 dark:bg-sky-950/20">
            <div class="w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center text-white text-sm font-bold">AI</div>
            <div>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">IT 助手</p>
                <p class="text-xs text-zinc-500">可以查询你的工单、任务、项目、资产</p>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="ai-chat-messages" x-data x-init="$nextTick(() => { const el = document.getElementById('ai-chat-messages'); el.scrollTop = el.scrollHeight; })">
            @foreach($messages as $msg)
            @if($msg['role'] === 'user')
            <div class="flex justify-end">
                <div class="max-w-[80%] px-4 py-2 bg-sky-600 text-white text-sm rounded-2xl rounded-br-md">{{ $msg['content'] }}</div>
            </div>
            @else
            <div class="flex gap-2">
                <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/40 flex items-center justify-center text-sky-600 text-xs font-bold shrink-0 mt-1">AI</div>
                <div class="max-w-[85%] px-4 py-2 bg-zinc-100 dark:bg-zinc-800 text-sm text-zinc-800 dark:text-zinc-200 rounded-2xl rounded-bl-md whitespace-pre-wrap">{{ $msg['content'] }}</div>
            </div>
            @endif
            @endforeach

            @if($loading)
            <div class="flex gap-2">
                <div class="w-7 h-7 rounded-lg bg-sky-100 dark:bg-sky-950/40 flex items-center justify-center text-sky-600 text-xs font-bold shrink-0 mt-1">AI</div>
                <div class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-2xl rounded-bl-md">
                    <span class="inline-flex gap-1"><span class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce"></span><span class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce" style="animation-delay:0.1s"></span><span class="w-2 h-2 rounded-full bg-zinc-400 animate-bounce" style="animation-delay:0.2s"></span></span>
                </div>
            </div>
            @endif
        </div>

        {{-- Input --}}
        <div class="shrink-0 p-3 border-t border-zinc-100 dark:border-zinc-800">
            <div class="flex gap-2">
                <input wire:model="input" wire:keydown.enter="send" type="text" placeholder="问 IT 助手..."
                    class="flex-1 px-4 py-2 text-sm bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl focus:outline-none focus:border-sky-500 dark:text-white">
                <button wire:click="send" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium bg-sky-600 hover:bg-sky-500 text-white rounded-xl transition-colors disabled:opacity-50">
                    <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                    <span wire:loading>...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
