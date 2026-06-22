<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;

class AiChat extends Component
{
    public bool $isOpen = false;
    public array $messages = [];
    public string $input = '';
    public bool $loading = false;

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen && empty($this->messages)) {
            $this->messages[] = ['role' => 'assistant', 'content' => __('你好！我是 IT 助手。你可以问我关于你的工单、任务、项目、资产的问题。')];
        }
    }

    public function send(): void
    {
        $input = trim($this->input);
        if (empty($input)) return;

        // [REVIEW-FIX-R4 #6 P2] LLM 调用限流：防止用户频繁调用外部 API 导致成本失控。
        // 限制：每用户每分钟最多 10 次、每小时最多 60 次。
        // 超限时返回友好提示而非调用 LLM。
        $userKey = 'ai_chat_rate:' . auth()->id();
        $minuteCount = (int) \Illuminate\Support\Facades\Cache::get($userKey . ':min', 0);
        $hourCount = (int) \Illuminate\Support\Facades\Cache::get($userKey . ':hr', 0);

        if ($minuteCount >= 10) {
            $this->messages[] = ['role' => 'assistant', 'content' => __('请求过于频繁，请稍后再试。')];
            return;
        }
        if ($hourCount >= 60) {
            $this->messages[] = ['role' => 'assistant', 'content' => __('本小时请求次数已达上限，请稍后再试。')];
            return;
        }

        // 递增计数器
        \Illuminate\Support\Facades\Cache::put($userKey . ':min', $minuteCount + 1, 60);
        \Illuminate\Support\Facades\Cache::put($userKey . ':hr', $hourCount + 1, 3600);

        $this->messages[] = ['role' => 'user', 'content' => $input];
        $this->input = '';
        $this->loading = true;

        try {
            $reply = $this->callLlm();
            $this->messages[] = ['role' => 'assistant', 'content' => $reply];
        } catch (\Exception $e) {
            // [REVIEW-FIX] R10.2: 生产环境不暴露内部错误详情
            $this->messages[] = ['role' => 'assistant', 'content' => __('抱歉，AI 服务暂时不可用，请稍后重试。') . (app()->isProduction() ? '' : ' (' . $e->getMessage() . ')')];
        }

        $this->loading = false;
        // 只保留最近 20 条消息
        if (count($this->messages) > 22) {
            $this->messages = array_slice($this->messages, -20);
            array_unshift($this->messages, ['role' => 'assistant', 'content' => __('你好！我是 IT 助手。')]);
        }
    }

    private function callLlm(): string
    {
        $url = config('services.llm.url', '');
        $key = config('services.llm.key', '');
        $model = config('services.llm.model', 'gpt-4o-mini');

        if (empty($url) || empty($key)) {
            return __('AI 助手未配置。请联系管理员在 AI 配置中设置 LLM API。');
        }

        // 构建用户上下文
        $context = $this->buildContext();

        $systemPrompt = __("你是 IT 服务管理系统的 AI 助手。你可以用中文回答用户关于他们工单、任务、项目、资产的问题。\n")
            . __("以下是当前用户的信息，请基于这些数据回答问题。如果用户问的问题跟这些数据无关，简要说明你只能回答 IT 相关的问题。\n\n")
            . $context;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // 最近 10 条对话历史
        foreach (array_slice($this->messages, -10) as $msg) {
            $messages[] = $msg;
        }

        $resp = Http::withToken($key)
            ->timeout(30)
            ->post($url, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 600,
                'temperature' => 0.7,
            ]);

        if ($resp->failed()) {
            // [REVIEW-FIX] SP5.2: 生产环境不暴露 API 错误详情，仅记录日志
            \Illuminate\Support\Facades\Log::error('LLM API failed', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            return __('AI 服务暂时不可用，请稍后重试。') . (app()->isProduction() ? '' : ' (' . $resp->status() . ': ' . ($resp->json()['error']['message'] ?? __('未知错误')) . ')');
        }

        return $resp->json()['choices'][0]['message']['content'] ?? __('未收到回复');
    }

    private function buildContext(): string
    {
        // [REVIEW-FIX] R10.1: 管理员可关闭上下文注入，防止敏感数据不经意外泄至外部 LLM
        if (!config('services.llm.send_context', true)) {
            return __("用户未授权发送工单/任务/项目数据。");
        }
        $user = auth()->user();
        $lines = [];

        // 工单 — [REVIEW-FIX] I2: orWhere 包裹在嵌套 where()
        $tickets = \App\Models\Ticket::where(function ($q) use ($user) {
            $q->where('assigned_to', $user->id)
              ->orWhere('created_by', $user->id);
        })
            ->with('assignee')  // [REVIEW-FIX] SP5.1: 预加载 assignee 消除 N+1 查询
            ->latest()->limit(10)->get();

        if ($tickets->isNotEmpty()) {
            $lines[] = __("## 工单（最近10条）");
            foreach ($tickets as $t) {
                $lines[] = __('- #:id [:priority] :title → :status', [
                    'id' => $t->id,
                    'priority' => $t->priorityLabel . __('优先'),
                    'title' => $t->title,
                    'status' => $t->statusLabel,
                ]) . ($t->assignee ? ' (' . $t->assignee->name . ')' : '');
            }
            $lines[] = "";
        }

        // 任务
        $tasks = \App\Models\Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->with('project')->latest()->limit(5)->get();

        if ($tasks->isNotEmpty()) {
            $lines[] = __("## 进行中的任务");
            foreach ($tasks as $t) {
                $lines[] = __('- :title [:status] → 项目: :project', [
                    'title' => $t->title,
                    'status' => $t->statusLabel,
                    'project' => $t->project?->title ?? __('未知项目'),
                ]);
            }
            $lines[] = "";
        }

        // 项目 — [REVIEW-FIX] I2: orWhere 包裹在嵌套 where()
        $projects = \App\Models\Project::where(function ($q) use ($user) {
            $q->whereHas('members', fn($q2) => $q2->where('user_id', $user->id))
              ->orWhere('created_by', $user->id);
        })
            ->latest()->limit(5)->get();

        if ($projects->isNotEmpty()) {
            $lines[] = __("## 参与的项目（最近5个）");
            foreach ($projects as $p) {
                $lines[] = "- {$p->title} [{$p->progressLabel}] {$p->completion_percent}%";
            }
            $lines[] = "";
        }

        // 资产
        $assets = \App\Models\Asset::where('assigned_to', $user->id)->latest()->limit(5)->get();
        if ($assets->isNotEmpty()) {
            $lines[] = __("## 资产");
            foreach ($assets as $a) {
                $warranty = $a->warranty_expiry ? __('保修至:date', ['date' => $a->warranty_expiry->format('Y-m-d')]) : "";
                $lines[] = "- {$a->name} ({$a->asset_tag}) {$a->statusLabel} {$warranty}";
            }
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('livewire.ai-chat');
    }
}
