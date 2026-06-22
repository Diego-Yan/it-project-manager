<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class LdapAuthService
{
    private $connection;
    private $config;
    private $bound = false;

    public function __construct()
    {
        $this->config = config('ad-auth');
    }

    /**
     * 连接到 AD 服务器
     */
    public function connect(): bool
    {
        if (!$this->config['enabled']) {
            Log::warning(__('AD 认证未启用'));
            return false;
        }

        // 兼容 server 里已包含协议前缀的情况（如 ldap://10.10.0.8）
        $serverRaw = $this->config['server'];
        $serverHost = preg_replace('#^ldaps?://#i', '', $serverRaw);

        $host = $this->config['use_ssl']
            ? 'ldaps://' . $serverHost
            : 'ldap://' . $serverHost;
        $port = $this->config['port'];

        $this->connection = @ldap_connect($host, $port);

        if (!$this->connection) {
            Log::error(__('无法连接到 AD 服务器'), ['host' => $host, 'port' => $port]);
            return false;
        }

        // 设置 LDAP 协议版本
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

        // 启用 TLS
        if ($this->config['use_tls']) {
            if (!@ldap_start_tls($this->connection)) {
                Log::error(__('无法启动 TLS'));
                return false;
            }
        }

        // 管理员绑定
        if (!empty($this->config['admin_username']) && !empty($this->config['admin_password'])) {
            $bindDn = $this->config['admin_username'] . '@' . $this->config['domain'];
            if (!@ldap_bind($this->connection, $bindDn, $this->config['admin_password'])) {
                Log::error(__('AD 管理员绑定失败'), ['error' => ldap_error($this->connection)]);
                return false;
            }
        }

        $this->bound = true;
        Log::info(__('AD 连接成功'), ['host' => $host, 'port' => $port]);
        return true;
    }

    /**
     * 用户认证
     */
    public function authenticate(string $username, string $password): ?User
    {
        // 缓存检查：失败次数过多则锁定
        $lockKey = 'ad_auth_lock:' . $username;
        if (Cache::has($lockKey)) {
            Log::warning(__('AD 认证被锁定'), ['username' => $username]);
            return null;
        }

        if (!$this->bound) {
            if (!$this->connect()) {
                return null;
            }
        }

        // 尝试 AD 认证
        $userDn = $this->findUserDn($username);
        if (!$userDn) {
            $this->recordFailedAttempt($username);
            Log::warning(__('AD 用户不存在'), ['username' => $username]);
            return null;
        }

        // 用户密码验证
        $bindResult = @ldap_bind($this->connection, $userDn, $password);
        if (!$bindResult) {
            $this->recordFailedAttempt($username);
            Log::warning(__('AD 认证失败'), ['username' => $username, 'error' => ldap_error($this->connection)]);
            return null;
        }

        // 认证成功，获取用户信息
        $userInfo = $this->getUserInfo($userDn);
        if (!$userInfo) {
            return null;
        }

        // 清除失败记录
        Cache::forget($lockKey);

        // 同步或更新本地用户
        $user = $this->syncUser($username, $userInfo);
        if ($user) {
            Log::info(__('AD 认证成功'), ['username' => $username, 'local_id' => $user->id]);
        }

        return $user;
    }

    /**
     * 查找用户 DN
     */
    private function findUserDn(string $username): ?string
    {
        $escaped = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $filter = "(&(objectClass=user)(sAMAccountName={$escaped}))";
        $search = @ldap_search($this->connection, $this->config['base_dn'], $filter, ['dn']);

        if (!$search) {
            return null;
        }

        $entries = ldap_get_entries($this->connection, $search);
        if ($entries['count'] === 0) {
            return null;
        }

        return $entries[0]['dn'];
    }

    /**
     * 获取用户信息
     */
    private function getUserInfo(string $userDn): ?array
    {
        $search = @ldap_read($this->connection, $userDn, '(objectClass=*)', [
            'displayname',
            'mail',
            'telephonenumber',
            'department',
            'title',
            'memberof'
        ]);

        if (!$search) {
            return null;
        }

        $entries = ldap_get_entries($this->connection, $search);
        if ($entries['count'] === 0) {
            return null;
        }

        $info = [
            'display_name' => $entries[0]['displayname'][0] ?? null,
            'email' => $entries[0]['mail'][0] ?? null,
            'phone' => $entries[0]['telephonenumber'][0] ?? null,
            'department' => $entries[0]['department'][0] ?? null,
            'title' => $entries[0]['title'][0] ?? null,
        ];

        return $info;
    }

    /**
     * 同步用户到本地数据库
     */
    private function syncUser(string $username, array $userInfo): ?User
    {
        // [REVIEW-FIX] SP5.3: 移除 orWhere username — 防止 AD 认证覆盖本地同名账号数据（与 AdSyncUsers P0.4 一致）
        $user = User::where('ad_username', $username)->first();

        if (!$user && $this->config['sync']['auto_create_user']) {
            // 创建新用户
            $user = User::create([
                'name' => $userInfo['display_name'] ?? $username,
                'username' => $username, // [REVIEW-FIX] M5: AD 用户同步时设置 username
                'email' => $userInfo['email'] ?? $username . '@' . $this->config['domain'],
                'ad_domain' => $this->config['domain'],
                'ad_username' => $username,
                'ad_display_name' => $userInfo['display_name'],
                'ad_email' => $userInfo['email'],
                'ad_authenticated' => true,
                'ad_last_sync_at' => now(),
                'is_active' => true, // [REVIEW-FIX] R17.1: 补全缺失字段，防止依赖 DB 默认值
                'source' => 'ad',   // [REVIEW-FIX] R17.1: 标记来源为 AD
                'password' => bcrypt(\Illuminate\Support\Str::random(32)), // [FIX] #3: 随机不可预测密码
            ]);

            // 分配默认角色
            $defaultRole = $this->config['sync']['default_role'];
            if ($defaultRole && method_exists($user, 'assignRole')) {
                $user->assignRole($defaultRole);
            }

            Log::info(__('AD 用户已创建'), ['username' => $username, 'user_id' => $user->id]);
        } elseif ($user) {
            // 更新现有用户
            $user->update([
                'ad_display_name' => $userInfo['display_name'],
                'ad_email' => $userInfo['email'],
                'ad_last_sync_at' => now(),
            ]);
        }

        return $user;
    }

    /**
     * 记录失败尝试
     */
    private function recordFailedAttempt(string $username): void
    {
        $key = 'ad_auth_attempts:' . $username;
        $attempts = Cache::increment($key);

        if ($attempts >= $this->config['lock_after_failed_attempts']) {
            Cache::put('ad_auth_lock:' . $username, true, now()->addMinutes($this->config['lock_minutes']));
            Cache::forget($key);
        }
    }


    /**
     * 搜索 AD 用户（关键词匹配 sAMAccountName/displayName/mail）
     */
    public function searchUsers(string $keyword, int $limit = 20): array
    {
        if (!$this->bound && !$this->connect()) {
            return [];
        }

        $escaped = ldap_escape($keyword, '', LDAP_ESCAPE_FILTER);
        $filter = "(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|(sAMAccountName=*{$escaped}*)(displayName=*{$escaped}*)(mail=*{$escaped}*)))";

        $search = @ldap_search(
            $this->connection,
            $this->config['base_dn'],
            $filter,
            ['sAMAccountName', 'displayName', 'mail', 'department', 'telephonenumber', 'title'],
            0, $limit
        );

        if (!$search) {
            Log::error(__('AD searchUsers 搜索失败'), ['keyword' => $keyword, 'error' => ldap_error($this->connection)]);
            return [];
        }

        $entries = ldap_get_entries($this->connection, $search);
        $results = [];

        for ($i = 0; $i < $entries['count']; $i++) {
            $e = $entries[$i];
            $results[] = [
                'username'   => $e['samaccountname'][0] ?? '',
                'name'       => $e['displayname'][0] ?? ($e['samaccountname'][0] ?? ''),
                'email'      => $e['mail'][0] ?? '',
                'department' => $e['department'][0] ?? '',
                'phone'      => $e['telephonenumber'][0] ?? '',
                'title'      => $e['title'][0] ?? '',
            ];
        }

        return $results;
    }

    /**
     * 根据 sAMAccountName 获取 AD 用户详细信息
     */
    public function getUserInfoByUsername(string $username): ?array
    {
        if (!$this->bound && !$this->connect()) {
            return null;
        }

        $escaped = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $filter  = "(&(objectClass=user)(sAMAccountName={$escaped}))";
        $search  = @ldap_search(
            $this->connection,
            $this->config['base_dn'],
            $filter,
            ['sAMAccountName', 'displayName', 'mail', 'department', 'telephonenumber', 'title']
        );

        if (!$search) {
            return null;
        }

        $entries = ldap_get_entries($this->connection, $search);
        if ($entries['count'] === 0) {
            return null;
        }

        $e = $entries[0];
        return [
            'username'   => $e['samaccountname'][0] ?? $username,
            'name'       => $e['displayname'][0] ?? $username,
            'email'      => $e['mail'][0] ?? '',
            'department' => $e['department'][0] ?? '',
            'phone'      => $e['telephonenumber'][0] ?? '',
            'title'      => $e['title'][0] ?? '',
        ];
    }

    /**
     * 关闭连接
     */
    public function __destruct()
    {
        if ($this->connection && $this->bound) {
            @ldap_close($this->connection);
        }
    }
}
