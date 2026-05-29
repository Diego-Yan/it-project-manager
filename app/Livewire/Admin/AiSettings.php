<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class AiSettings extends Component
{
    public string $embeddingUrl = '';
    public string $embeddingKey = '';
    public string $embeddingModel = '';
    public string $testResult = '';

    public function mount(): void
    {
        $this->embeddingUrl = (string) config('services.embedding.url', '');
        $this->embeddingKey = (string) config('services.embedding.key', '');
        $this->embeddingModel = (string) config('services.embedding.model', '');
    }

    public function save(): void
    {
        $this->updateEnv([
            'EMBEDDING_API_URL' => $this->embeddingUrl,
            'EMBEDDING_API_KEY' => $this->embeddingKey,
            'EMBEDDING_MODEL' => $this->embeddingModel,
        ]);
        session()->flash('success', 'Embedding API 配置已保存');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
    }

    public function test(): void
    {
        if (empty($this->embeddingUrl) || empty($this->embeddingKey)) {
            $this->testResult = '请先填写 API 地址和密钥';
            return;
        }

        try {
            $resp = \Illuminate\Support\Facades\Http::withToken($this->embeddingKey)
                ->timeout(10)
                ->post($this->embeddingUrl, [
                    'model' => $this->embeddingModel ?: 'text-embedding-3-small',
                    'input' => 'test',
                ]);

            if ($resp->successful()) {
                $data = $resp->json();
                $dim = count($data['data'][0]['embedding'] ?? []);
                $this->testResult = "连接成功 ✓ (维度: {$dim})";
            } else {
                $this->testResult = "API 返回错误: " . $resp->status();
            }
        } catch (\Exception $e) {
            $this->testResult = '连接失败: ' . $e->getMessage();
        }
    }

    private function updateEnv(array $updates): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) return;

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
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
        file_put_contents($envPath, implode("\n", $newLines) . "\n");
    }

    public function render()
    {
        return view('livewire.admin.ai-settings')
            ->layout('layouts.app', ['title' => 'AI 配置']);
    }
}
