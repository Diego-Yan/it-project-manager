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
            $this->messages[] = ['role' => 'assistant', 'content' => '你好！我是 IT 助手。你可以问我关于你的工单、任务、项目、资产的问题。'];
        }
    }

    public function send(): void
    {
        $input = trim($this->input);
        if (empty($input)) return;

        $this->messages[] = ['role' => 'user', 'content' => $input];
        $this->input = '';
        $this->loading = true;

        try {
            $reply = $this->callLlm();
            $this->messages[] = ['role' => 'assistant', 'content' => $reply];
        } catch (\Exception $e) {
            $this->messages[] = ['role' => 'assistant', 'content' => '抱歉，AI 服务暂时不可用：' . $e->getMessage()];
        }

        $this->loading = false;
        // 只保留最近 20 条消息
        if (count($this->messages) > 22) {
            $this->messages = array_slice($this->messages, -20);
            array_unshift($this->messages, ['role' => 'assistant', 'content' => '你好！我是 IT 助手。']);
        }
    }

    private function callLlm(): string
    {
        $url = config('services.llm.url', '');
        $key = config('services.llm.key', '');
        $model = config('services.llm.model', 'gpt-4o-mini');

        if (empty($url) || empty($key)) {
            return 'AI 助手未配置。请联系管理员在 AI 配置中设置 LLM API。';
        }

        // 构建用户上下文
        $context = $this->buildContext();

        $systemPrompt = "你是 IT 运营管理系统的 AI 助手。你可以用中文回答用户关于他们工单、任务、项目、资产的问题。\n"
            . "以下是当前用户的信息，请基于这些数据回答问题。如果用户问的问题跟这些数据无关，简要说明你只能回答 IT 相关的问题。\n\n"
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
            return 'AI API 返回错误 (' . $resp->status() . '): ' . ($resp->json()['error']['message'] ?? '未知错误');
        }

        return $resp->json()['choices'][0]['message']['content'] ?? '未收到回复';
    }

    private function buildContext(): string
    {
        $user = auth()->user();
        $lines = [];

        // 工单
        $tickets = \App\Models\Ticket::where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id)
            ->latest()->limit(10)->get();

        if ($tickets->isNotEmpty()) {
            $lines[] = "## 工单（最近10条）";
            foreach ($tickets as $t) {
                $lines[] = "- #{$t->id} [{$t->priorityLabel}优先] {$t->title} → {$t->statusLabel}" . ($t->assignee ? " ({$t->assignee->name})" : "");
            }
            $lines[] = "";
        }

        // 任务
        $tasks = \App\Models\Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->with('project')->latest()->limit(5)->get();

        if ($tasks->isNotEmpty()) {
            $lines[] = "## 进行中的任务";
            foreach ($tasks as $t) {
                $lines[] = "- {$t->title} [{$t->statusLabel}] → 项目: {$t->project->title}";
            }
            $lines[] = "";
        }

        // 项目
        $projects = \App\Models\Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->orWhere('created_by', $user->id)
            ->latest()->limit(5)->get();

        if ($projects->isNotEmpty()) {
            $lines[] = "## 参与的项目（最近5个）";
            foreach ($projects as $p) {
                $lines[] = "- {$p->title} [{$p->progressLabel}] {$p->completion_percent}%";
            }
            $lines[] = "";
        }

        // 资产
        $assets = \App\Models\Asset::where('assigned_to', $user->id)->latest()->limit(5)->get();
        if ($assets->isNotEmpty()) {
            $lines[] = "## 资产";
            foreach ($assets as $a) {
                $warranty = $a->warranty_expiry ? "保修至{$a->warranty_expiry->format('Y-m-d')}" : "";
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
