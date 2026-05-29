<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class AdSettingsManager extends Component
{
    // AD 开关
    public bool $adEnabled = false;

    // 服务器配置
    public string $adServer = '';
    public string $adPort = '389';
    public bool $adUseTls = false;
    public bool $adUseSsl = false;

    // 域配置
    public string $adDomain = '';
    public string $adBaseDn = '';

    // 管理员账号
    public string $adAdminUsername = '';
    public string $adAdminPassword = '';

    // 同步配置
    public bool $adAutoCreateUser = true;
    public bool $adAutoSyncGroups = false;
    public string $adDefaultRole = '';
    public string $adSyncInterval = '60';

    // 认证失败处理
    public bool $adFallbackToLocal = true;
    public string $adLockAfterFailed = '5';
    public string $adLockMinutes = '30';

    // 状态
    public bool $showPassword = false;
    public string $testStatus = '';   // '', 'testing', 'success', 'fail'
    public string $testMessage = '';
    public string $syncStatus = '';
    public string $syncMessage = '';

    public function mount(): void
    {
        $this->loadFromEnv();
    }

    private function loadFromEnv(): void
    {
        // 直接解析 .env 文件，避免 Laravel config cache 的读取延迟
        $env = $this->parseEnvFile();

        $this->adEnabled         = ($env['AD_AUTH_ENABLED'] ?? 'false') === 'true';
        $this->adServer          = $this->cleanServer($env['AD_SERVER'] ?? '');
        $this->adPort            = $env['AD_PORT'] ?? '389';
        $this->adUseTls          = ($env['AD_USE_TLS'] ?? 'false') === 'true';
        $this->adUseSsl          = ($env['AD_USE_SSL'] ?? 'false') === 'true';
        $this->adDomain          = $env['AD_DOMAIN'] ?? '';
        $this->adBaseDn          = $env['AD_BASE_DN'] ?? '';
        $this->adAdminUsername   = $env['AD_ADMIN_USERNAME'] ?? '';
        $this->adAdminPassword   = $env['AD_ADMIN_PASSWORD'] ?? '';
        $this->adAutoCreateUser  = ($env['AD_AUTO_CREATE_USER'] ?? 'true') === 'true';
        $this->adAutoSyncGroups  = ($env['AD_AUTO_SYNC_GROUPS'] ?? 'false') === 'true';
        $this->adDefaultRole     = $env['AD_DEFAULT_ROLE'] ?? '普通员工';
        $this->adSyncInterval    = $env['AD_SYNC_INTERVAL'] ?? '60';
        $this->adFallbackToLocal = ($env['AD_FALLBACK_TO_LOCAL'] ?? 'true') === 'true';
        $this->adLockAfterFailed = $env['AD_LOCK_AFTER_FAILED'] ?? '5';
        $this->adLockMinutes     = $env['AD_LOCK_MINUTES'] ?? '30';
    }

    /**
     * 直接解析 .env 文件，返回键值对数组
     */
    private function parseEnvFile(): array
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return [];
        }

        $result = [];
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // 如果同一个 key 出现多次，只取第一个（与 dotenv 行为一致）
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eqPos = strpos($line, '=');
            if ($eqPos === false) continue;

            $key = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            // 去掉首尾引号
            if (strlen($value) >= 2 && (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            )) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $result)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function cleanServer(string $server): string
    {
        return preg_replace('#^ldaps?://#', '', $server);
    }

    public function save(): void
    {
        $this->validate([
            'adServer'          => 'required|string',
            'adPort'            => 'required|numeric|min:1|max:65535',
            'adDomain'          => 'required|string',
            'adBaseDn'          => 'required|string',
            'adAdminUsername'   => 'nullable|string',
            'adLockAfterFailed' => 'required|numeric|min:1',
            'adLockMinutes'     => 'required|numeric|min:1',
            'adSyncInterval'    => 'required|numeric|min:5',
        ], [
            'adServer.required'  => 'AD服务器地址不能为空',
            'adDomain.required'  => '域名不能为空',
            'adBaseDn.required'  => 'Base DN不能为空',
        ]);

        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            session()->flash('error', '.env 文件不存在，无法保存配置。');
            return;
        }

        $serverUrl = $this->adUseSsl
            ? 'ldaps://' . trim($this->adServer)
            : 'ldap://' . trim($this->adServer);

        $updates = [
            'AD_AUTH_ENABLED'      => $this->adEnabled ? 'true' : 'false',
            'AD_SERVER'            => $serverUrl,
            'AD_PORT'              => $this->adPort,
            'AD_USE_TLS'           => $this->adUseTls ? 'true' : 'false',
            'AD_USE_SSL'           => $this->adUseSsl ? 'true' : 'false',
            'AD_DOMAIN'            => $this->adDomain,
            'AD_BASE_DN'           => $this->adBaseDn,
            'AD_ADMIN_USERNAME'    => $this->adAdminUsername,
            'AD_AUTO_CREATE_USER'  => $this->adAutoCreateUser ? 'true' : 'false',
            'AD_AUTO_SYNC_GROUPS'  => $this->adAutoSyncGroups ? 'true' : 'false',
            'AD_DEFAULT_ROLE'      => $this->adDefaultRole,
            'AD_FALLBACK_TO_LOCAL' => $this->adFallbackToLocal ? 'true' : 'false',
            'AD_LOCK_AFTER_FAILED' => $this->adLockAfterFailed,
            'AD_LOCK_MINUTES'      => $this->adLockMinutes,
            'AD_SYNC_INTERVAL'     => $this->adSyncInterval,
        ];

        if (!empty($this->adAdminPassword)) {
            $updates['AD_ADMIN_PASSWORD'] = $this->adAdminPassword;
        }

        $this->writeEnvValues($envPath, $updates);

        // 清除配置缓存
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            Log::warning('配置缓存清除失败: ' . $e->getMessage());
        }

        // 重新加载当前显示（从文件读取，避免受 PHP env cache 影响）
        $this->loadFromEnv();

        session()->flash('success', 'AD 域配置已保存并生效。');
        $this->testStatus = '';
        $this->testMessage = '';
    }

    /**
     * 写入 .env 文件中的键值对
     * 核心修复：读取 -> 重建每行 -> 写回，避免重复 key 和特殊字符问题
     */
    private function writeEnvValues(string $envPath, array $updates): void
    {
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
                $newLines[] = $key . '=' . $this->envQuote($value);
                $written[$key] = true;
            } else {
                $newLines[] = $line;
            }
        }

        // 追加还没有出现过的新 key
        foreach ($updates as $key => $value) {
            if (!isset($written[$key])) {
                $newLines[] = $key . '=' . $this->envQuote($value);
            }
        }

        file_put_contents($envPath, implode("\n", $newLines) . "\n");
    }

    /**
     * 如果值含空格、特殊字符或引号，用双引号包裹
     */
    private function envQuote(string $value): string
    {
        if ($value === '') return '';

        // 如果含有空格、# = $ ! < > & | 等特殊字符则加引号
        if (preg_match('/[\s#=\$!<>&|\'"`\\\\]/', $value)) {
            // 内部双引号转义
            $value = str_replace('"', '\\"', $value);
            return '"' . $value . '"';
        }

        return $value;
    }

    public function testConnection(): void
    {
        if (empty($this->adServer) || empty($this->adDomain)) {
            $this->testStatus  = 'fail';
            $this->testMessage = '请先填写服务器地址和域名。';
            return;
        }

        $this->testStatus  = 'testing';
        $this->testMessage = '正在连接...';

        try {
            if (!function_exists('ldap_connect')) {
                $this->testStatus  = 'fail';
                $this->testMessage = 'PHP LDAP 扩展未安装，请运行: sudo apt install php-ldap 并重启服务。';
                return;
            }

            $host = $this->adUseSsl
                ? 'ldaps://' . trim($this->adServer)
                : 'ldap://' . trim($this->adServer);

            $conn = @ldap_connect($host, (int) $this->adPort);

            if (!$conn) {
                $this->testStatus  = 'fail';
                $this->testMessage = "无法连接到 {$host}:{$this->adPort}，请检查服务器地址和端口。";
                return;
            }

            ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 5);

            // 如果有管理员账号，用它绑定；否则匿名
            if (!empty($this->adAdminUsername) && !empty($this->adAdminPassword)) {
                $bindUser = $this->adAdminUsername . '@' . $this->adDomain;
                $bindResult = @ldap_bind($conn, $bindUser, $this->adAdminPassword);
            } else {
                $bindResult = @ldap_bind($conn);
            }

            if (!$bindResult) {
                $ldapErr = ldap_errno($conn);
                if ($ldapErr === 49) {
                    $this->testStatus  = 'fail';
                    $this->testMessage = "连接到达 {$host}:{$this->adPort}，但账号或密码错误（错误49）。请检查管理员账号密码。";
                } elseif ($ldapErr === 0) {
                    $this->testStatus  = 'success';
                    $this->testMessage = "✓ AD 服务器可达（{$host}:{$this->adPort}）。";
                } else {
                    $this->testStatus  = 'fail';
                    $this->testMessage = "连接失败，LDAP 错误 {$ldapErr}: " . ldap_error($conn);
                }
            } else {
                $this->testStatus  = 'success';
                $this->testMessage = "✓ 成功连接并绑定到 AD 服务器（{$host}:{$this->adPort}），认证正常。";
            }

            @ldap_close($conn);

        } catch (\Exception $e) {
            $this->testStatus  = 'fail';
            $this->testMessage = '连接异常：' . $e->getMessage();
        }
    }

    public function syncNow(): void
    {
        $this->syncStatus  = 'running';
        $this->syncMessage = '正在同步 AD 账号...';

        try {
            Artisan::call('ad:sync-users');
            $output = Artisan::output();
            $this->syncStatus  = 'success';
            $this->syncMessage = '✓ 同步完成。' . trim($output);
        } catch (\Exception $e) {
            $this->syncStatus  = 'fail';
            $this->syncMessage = '同步失败：' . $e->getMessage();
        }
    }

    public function render()
    {
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('livewire.admin.ad-settings-manager', compact('roles'))
            ->layout('layouts.app', ['title' => 'AD 域配置']);
    }
}
