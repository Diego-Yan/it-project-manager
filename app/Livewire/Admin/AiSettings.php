<?php

namespace App\Livewire\Admin;

use App\Services\EnvService;
use Livewire\Component;

class AiSettings extends Component
{
    // [REVIEW-FIX] R4.1: URL/Model 公开，Key 不在 mount 中预填充（防止 Livewire 序列化泄露）
    public string $embeddingUrl = '';
    public string $embeddingKey = '';
    public string $embeddingModel = '';
    public string $llmUrl = '';
    public string $llmKey = '';
    public string $llmModel = '';
    public string $testResult = '';
    public string $llmTestResult = '';

    public function mount(): void
    {
        $this->embeddingUrl = (string) config('services.embedding.url', '');
        $this->embeddingModel = (string) config('services.embedding.model', '');
        $this->llmUrl = (string) config('services.llm.url', '');
        $this->llmModel = (string) config('services.llm.model', '');
        // [REVIEW-FIX] R4.1: 不在 mount 中加载 key，防止序列化到前端
        // 用户需要手动输入或留空保留原值；测试时从 config 临时读取
    }

    /**
     * [REVIEW-FIX] R4.1: dehydrate 时清除敏感字段，保证不会序列化到前端
     */
    public function dehydrate(): void
    {
        $this->embeddingKey = '';
        $this->llmKey = '';
    }

    public function save(): void
    {
        $this->guard(); // [REVIEW-FIX] R17.5
        $keyEmbedding = $this->embeddingKey ?: config('services.embedding.key', '');
        $keyLlm = $this->llmKey ?: config('services.llm.key', '');

        $this->updateEnv([
            'EMBEDDING_API_URL' => $this->embeddingUrl,
            'EMBEDDING_API_KEY' => $keyEmbedding,
            'EMBEDDING_MODEL' => $this->embeddingModel,
            'LLM_API_URL' => $this->llmUrl,
            'LLM_API_KEY' => $keyLlm,
            'LLM_MODEL' => $this->llmModel,
        ]);
        session()->flash('success', __('AI 配置已保存'));
    }

    public function testEmbedding(): void
    {
        $this->guard(); // [REVIEW-FIX] R17.5
        $key = $this->embeddingKey ?: config('services.embedding.key', '');
        if (empty($this->embeddingUrl) || empty($key)) { $this->testResult = __('请先填写 API 地址和密钥'); return; }
        // [REVIEW-FIX-R5 #2 P2] SSRF 防护：LLM/Embedding API 地址不应指向内网
        if (!\App\Services\SsrfGuard::isSafe($this->embeddingUrl)) {
            $this->testResult = __('不允许的 API 地址：不能指向内网或保留地址段。');
            return;
        }
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($key)->timeout(10)->post($this->embeddingUrl, ['model' => $this->embeddingModel ?: 'text-embedding-3-small', 'input' => 'test']);
            $data = $resp->json();
            $dim = count($data['data'][0]['embedding'] ?? []);
            $this->testResult = __('连接成功 ✓ (维度: :dim)', ['dim' => $dim]);
        } catch (\Exception $e) { $this->testResult = __('连接失败: :message', ['message' => $e->getMessage()]); }
    }

    public function testLlm(): void
    {
        $this->guard(); // [REVIEW-FIX] R17.5
        $key = $this->llmKey ?: config('services.llm.key', '');
        if (empty($this->llmUrl) || empty($key)) { $this->llmTestResult = __('请先填写 API 地址和密钥'); return; }
        // [REVIEW-FIX-R5 #2 P2] SSRF 防护：LLM API 地址不应指向内网
        if (!\App\Services\SsrfGuard::isSafe($this->llmUrl)) {
            $this->llmTestResult = __('不允许的 API 地址：不能指向内网或保留地址段。');
            return;
        }
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($key)->timeout(15)->post($this->llmUrl, [
                'model' => $this->llmModel ?: 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => __('你好，请用一句话介绍你自己')]],
                'max_tokens' => 50,
            ]);
            if ($resp->successful()) {
                $reply = $resp->json()['choices'][0]['message']['content'] ?? '';
                $this->llmTestResult = '✓ ' . mb_substr($reply, 0, 100);
            } else {
                $this->llmTestResult = __('API 返回错误: :status', ['status' => $resp->status()]);
            }
        } catch (\Exception $e) { $this->llmTestResult = __('连接失败: :message', ['message' => $e->getMessage()]); }
    }

    // [REVIEW-FIX] C3: updateEnv() 已提取至 app/Services/EnvService.php
    private function updateEnv(array $updates): void
    {
        EnvService::write($updates);
    }

    // [REVIEW-FIX] R17.5: Livewire action 绕过路由中间件，需内联权限检查
    private function guard(): void
    {
        if (!auth()->user()->can('manage roles')) abort(403);
    }

    public function render()
    {
        return view('livewire.admin.ai-settings')
            ->layout('layouts.app', ['title' => __('AI 配置')]);
    }
}
