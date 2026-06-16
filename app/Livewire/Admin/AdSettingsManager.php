<?php

namespace App\Livewire\Admin;

use App\Services\EnvService;
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
        // [REVIEW-FIX] C3: 使用共享 EnvService 替代重复的 parseEnvFile()
        $env = EnvService::parse();

        $this->adEnabled         = ($env['AD_AUTH_ENABLED'] ?? 'false') === 'true';
        $this->adServer          = $this->cleanServer($env['AD_SERVER'] ?? '');
        $this->adPort            = $env['AD_PORT'] ?? '389';
        $this->adUseTls          = ($env['AD_USE_TLS'] ?? 'false') === 'true';
        $this->adUseSsl          = ($env['AD_USE_SSL'] ?? 'false') === 'true';
        $this->adDomain          = $env['AD_DOMAIN'] ?? '';
        $this->adBaseDn          = $env['AD_BASE_DN'] ?? '';
        $this->adAdminUsername   = $env['AD_ADMIN_USERNAME'] ?? '';
        // [REVIEW-FIX] R4.3: 不在 mount 中加载 AD 管理员密码，防止 Livewire 序列化泄露
        $this->adAutoCreateUser  = ($env['AD_AUTO_CREATE_USER'] ?? 'true') === 'true';
        $this->adAutoSyncGroups  = ($env['AD_AUTO_SYNC_GROUPS'] ?? 'false') === 'true';
        $this->adDefaultRole     = $env['AD_DEFAULT_ROLE'] ?? '普通员工';
        $this->adSyncInterval    = $env['AD_SYNC_INTERVAL'] ?? '60';
        $this->adFallbackToLocal = ($env['AD_FALLBACK_TO_LOCAL'] ?? 'true') === 'true';
        $this->adLockAfterFailed = $env['AD_LOCK_AFTER_FAILED'] ?? '5';
        $this->adLockMinutes     = $env['AD_LOCK_MINUTES'] ?? '30';
    }

    private function cleanServer(string $server): string
    {
        return preg_replace('#^ldaps?://#', '', $server);
    }

    public function save(): void
    {
        $this->guard(); // [REVIEW-FIX] R3.1
        $this->validate([
            'adServer'          => 'required|string',
            'adPort'            => 'required|numeric|min:1|max:65535',
            'adDomain'          => 'required|string',
            'adBaseDn'          => 'required|string',
            'adAdminUsername'   => 'nullable|string',
            'adLockAfterFailed' => 'required|numeric|min:1',
            'adLockMinutes'     => 'required|numeric|min:1',
            'adSyncInterval'    => 'required|numeric|min:5',
            'adDefaultRole'     => 'required|string|exists:roles,name', // [REVIEW-FIX] N5: 验证角色存在
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

        // [REVIEW-FIX] R4.3: 密码留空时保留原值
        $currentEnv = EnvService::parse();
        $pwd = $this->adAdminPassword ?: ($currentEnv['AD_ADMIN_PASSWORD'] ?? '');
        if (!empty($pwd)) {
            $updates['AD_ADMIN_PASSWORD'] = $pwd;
        }

        // [REVIEW-FIX] C3: 使用共享 EnvService 替代私有 writeEnvValues()
        EnvService::write($updates);

        // 重新加载当前显示（从文件读取，避免受 PHP env cache 影响）
        $this->loadFromEnv();

        session()->flash('success', 'AD 域配置已保存并生效。');
        $this->testStatus = '';
        $this->testMessage = '';
    }

    // [REVIEW-FIX] C3: writeEnvValues() + envQuote() 已提取至 app/Services/EnvService.php

    public function testConnection(): void
    {
        $this->guard(); // [REVIEW-FIX] R3.1
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
            // [REVIEW-FIX] R16.5: 从 .env 读取密码（dehydrate 后会清除属性值）
            $env = EnvService::parse();
            $actualPassword = $env['AD_ADMIN_PASSWORD'] ?? '';
            if (!empty($this->adAdminUsername) && !empty($actualPassword)) {
                $bindUser = $this->adAdminUsername . '@' . $this->adDomain;
                $bindResult = @ldap_bind($conn, $bindUser, $actualPassword);
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
        $this->guard(); // [REVIEW-FIX] R3.1
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

    /**
     * [REVIEW-FIX] R4.3: dehydrate 时清除密码字段
     */
    public function dehydrate(): void
    {
        $this->adAdminPassword = '';
    }

    // [REVIEW-FIX] R3.1: Livewire action 绕过路由中间件，需内联权限检查
    private function guard(): void
    {
        if (!auth()->user()->can('manage roles')) abort(403);
    }

    public function render()
    {
        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('livewire.admin.ad-settings-manager', compact('roles'))
            ->layout('layouts.app', ['title' => 'AD 域配置']);
    }
}
