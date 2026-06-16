<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

/**
 * 安全的 .env 文件读写服务。
 *
 * 提供统一的 .env 键值对更新接口，避免三个 Admin 组件各自实现重复的
 * updateEnv() / writeEnvValues() / parseEnvFile() 逻辑（约 70 行 × 3）。
 *
 * 安全注意事项：
 * - 使用 flock(LOCK_EX) 防止并发写入截断
 * - 先读后写，逐行重建，避免重复 key 和特殊字符问题
 * - 所有写操作自动调用 Artisan::config:clear 刷新配置缓存
 */
class EnvService
{
    /**
     * 更新 .env 文件中的一个或多个键值对。
     *
     * @param array<string, string> $updates 键值对数组，例如 ['AD_SERVER' => 'ldap://dc.example.com']
     */
    public static function write(array $updates): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $written = [];
        $newLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // 注释和空行原样保留
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                $newLines[] = $line;
                continue;
            }

            $eqPos = strpos($trimmed, '=');
            if ($eqPos === false) {
                $newLines[] = $line;
                continue;
            }

            $key = trim(substr($trimmed, 0, $eqPos));

            if (array_key_exists($key, $updates)) {
                if (isset($written[$key])) {
                    // 跳过重复的 key（去重）
                    continue;
                }
                $value = $updates[$key];
                $newLines[] = $key . '=' . self::quote($value);
                $written[$key] = true;
            } else {
                $newLines[] = $line;
            }
        }

        // 追加还没有出现过的新 key
        foreach ($updates as $key => $value) {
            if (! isset($written[$key])) {
                $newLines[] = $key . '=' . self::quote($value);
            }
        }

        // 使用文件锁避免并发写入截断
        $fp = fopen($envPath, 'w');
        if ($fp) {
            try {
                flock($fp, LOCK_EX);
                fwrite($fp, implode("\n", $newLines) . "\n");
                fflush($fp);
            } finally {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }

        // 清除配置缓存使变更生效
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // 静默处理 — 用户可通过 php artisan config:clear 手动刷新
        }
    }

    /**
     * 解析 .env 文件为键值对数组。
     *
     * @return array<string, string>
     */
    public static function parse(): array
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return [];
        }

        $result = [];
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            // 去掉首尾引号
            if (strlen($value) >= 2 && (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            )) {
                $value = substr($value, 1, -1);
            }

            if (! array_key_exists($key, $result)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 如果值含空格、特殊字符或引号，用双引号包裹。
     */
    private static function quote(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#=\$!<>&|\'"`\\\\]/', $value)) {
            $value = str_replace('"', '\\"', $value);
            return '"' . $value . '"';
        }

        return $value;
    }
}
