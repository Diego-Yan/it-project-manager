<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class AiSettings extends Component
{
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
        $this->embeddingKey = (string) config('services.embedding.key', '');
        $this->embeddingModel = (string) config('services.embedding.model', '');
        $this->llmUrl = (string) config('services.llm.url', '');
        $this->llmKey = (string) config('services.llm.key', '');
        $this->llmModel = (string) config('services.llm.model', '');
    }

    public function save(): void
    {
        $this->updateEnv([
            'EMBEDDING_API_URL' => $this->embeddingUrl,
            'EMBEDDING_API_KEY' => $this->embeddingKey,
            'EMBEDDING_MODEL' => $this->embeddingModel,
            'LLM_API_URL' => $this->llmUrl,
            'LLM_API_KEY' => $this->llmKey,
            'LLM_MODEL' => $this->llmModel,
        ]);
        session()->flash('success', 'AI 配置已保存');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
    }

    public function testEmbedding(): void
    {
        if (empty($this->embeddingUrl) || empty($this->embeddingKey)) { $this->testResult = '请先填写 API 地址和密钥'; return; }
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($this->embeddingKey)->timeout(10)->post($this->embeddingUrl, ['model' => $this->embeddingModel ?: 'text-embedding-3-small', 'input' => 'test']);
            $data = $resp->json();
            $dim = count($data['data'][0]['embedding'] ?? []);
            $this->testResult = "连接成功 ✓ (维度: {$dim})";
        } catch (\Exception $e) { $this->testResult = '连接失败: ' . $e->getMessage(); }
    }

    public function testLlm(): void
    {
        if (empty($this->llmUrl) || empty($this->llmKey)) { $this->llmTestResult = '请先填写 API 地址和密钥'; return; }
        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($this->llmKey)->timeout(15)->post($this->llmUrl, [
                'model' => $this->llmModel ?: 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => '你好，请用一句话介绍你自己']],
                'max_tokens' => 50,
            ]);
            if ($resp->successful()) {
                $reply = $resp->json()['choices'][0]['message']['content'] ?? '';
                $this->llmTestResult = '✓ ' . mb_substr($reply, 0, 100);
            } else {
                $this->llmTestResult = 'API 返回错误: ' . $resp->status();
            }
        } catch (\Exception $e) { $this->llmTestResult = '连接失败: ' . $e->getMessage(); }
    }

    // [FIX] #12: 使用文件锁避免并发写入截断 .env
    private function updateEnv(array $updates): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $fp = fopen($envPath, 'r+');
        if (!$fp) return;

        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return;
            }

            $content = stream_get_contents($fp);
            $lines = explode("\n", $content);
            $written = [];
            $newLines = [];

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) { $newLines[] = $line; continue; }
                $eqPos = strpos($trimmed, '=');
                if ($eqPos === false) { $newLines[] = $line; continue; }
                $key = trim(substr($trimmed, 0, $eqPos));
                if (array_key_exists($key, $updates)) {
                    if (!isset($written[$key])) {
                        $newLines[] = $key . '=' . $updates[$key];
                        $written[$key] = true;
                    }
                } else {
                    $newLines[] = $line;
                }
            }
            foreach ($updates as $key => $value) {
                if (!isset($written[$key])) { $newLines[] = $key . '=' . $value; }
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, implode("\n", $newLines) . "\n");
            fflush($fp);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function render()
    {
        return view('livewire.admin.ai-settings')
            ->layout('layouts.app', ['title' => 'AI 配置']);
    }
}
