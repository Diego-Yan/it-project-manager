<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AdSyncUsers extends Command
{
    protected $signature   = 'ad:sync-users {--dry-run : 只显示将要同步的用户，不实际写入}';
    protected $description = '从 AD 域同步用户账号到本地数据库';

    public function handle(): int
    {
        if (!config('ad-auth.enabled')) {
            $this->warn('AD 认证未启用，跳过同步。');
            return 0;
        }

        $server    = config('ad-auth.server');
        $port      = (int) config('ad-auth.port', 389);
        $domain    = config('ad-auth.domain');
        $baseDn    = config('ad-auth.base_dn');
        $adminUser = config('ad-auth.admin_username');
        $adminPass = config('ad-auth.admin_password');
        $defaultRole = config('ad-auth.sync.default_role', '普通员工');
        $isDryRun  = $this->option('dry-run');

        if (empty($server) || empty($domain) || empty($baseDn)) {
            $this->error('AD 配置不完整（server/domain/base_dn 不能为空）。');
            return 1;
        }

        $this->info("开始从 AD 同步用户... 服务器: {$server}:{$port}");

        // 连接 LDAP
        $ldapConn = @ldap_connect($server, $port);
        if (!$ldapConn) {
            $this->error('无法连接到 AD 服务器。');
            Log::error('AD sync: 无法连接到 LDAP 服务器', ['server' => $server]);
            return 1;
        }

        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10);

        // 绑定管理员账号
        $bindUser = $adminUser . '@' . $domain;
        if (!@ldap_bind($ldapConn, $bindUser, $adminPass)) {
            $this->error('AD 管理员账号绑定失败，请检查用户名和密码。');
            Log::error('AD sync: 管理员账号绑定失败', ['user' => $bindUser]);
            ldap_close($ldapConn);
            return 1;
        }

        $this->info('AD 连接成功，开始搜索用户...');

        // 搜索所有启用的用户
        $filter = '(&(objectClass=person)(objectCategory=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
        $attrs  = ['sAMAccountName', 'cn', 'mail', 'department', 'telephoneNumber', 'distinguishedName', 'objectGUID'];

        $result = @ldap_search($ldapConn, $baseDn, $filter, $attrs, 0, 0, 30);
        if (!$result) {
            $this->error('LDAP 搜索失败: ' . ldap_error($ldapConn));
            ldap_close($ldapConn);
            return 1;
        }

        $entries = ldap_get_entries($ldapConn, $result);
        $total   = $entries['count'] ?? 0;
        $this->info("在 AD 中找到 {$total} 个用户。");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        for ($i = 0; $i < $total; $i++) {
            $entry = $entries[$i];

            $username    = strtolower($entry['samaccountname'][0] ?? '');
            $displayName = $entry['cn'][0] ?? $username;
            $email       = $entry['mail'][0] ?? null;
            $department  = $entry['department'][0] ?? null;
            $phone       = $entry['telephonenumber'][0] ?? null;

            if (empty($username)) {
                $skipped++;
                continue;
            }

            if ($isDryRun) {
                $this->line("  [dry-run] 用户: {$username} ({$displayName}) email:{$email}");
                continue;
            }

            // 查找或创建用户
            $user = User::where('ad_username', $username) // [REVIEW-FIX] P0.4: 移除 orWhere username，避免误匹配本地同名账号
                        ->first(); // [REVIEW-FIX] P0.4

            if ($user) {
                // 更新现有用户
                $user->name             = $displayName;
                $user->department       = $department;
                $user->phone            = $phone;
                $user->ad_authenticated = true;
                $user->ad_last_sync_at  = now();
                if ($email && !$user->email) {
                    $user->email = $email;
                }
                $user->save();
                $updated++;
            } else {
                // 创建新用户
                $user = User::create([
                    'name'             => $displayName,
                    'username'         => $username,
                    'email'            => $email,
                    'password'         => Hash::make(str()->random(32)),
                    'department'       => $department,
                    'phone'            => $phone,
                    'is_active'        => true,
                    'ad_authenticated' => true,
                    'ad_username'      => $username,
                    'ad_domain'        => $domain,
                    'ad_last_sync_at'  => now(),
                ]);

                // 分配默认角色
                $role = Role::where('name', $defaultRole)->first();
                if ($role) {
                    $user->assignRole($role);
                }

                $created++;
            }
        }

        ldap_close($ldapConn);

        if ($isDryRun) {
            $this->info("[dry-run] 模拟完成，共 {$total} 个用户（未写入数据库）。");
        } else {
            $this->info("同步完成！新建: {$created}，更新: {$updated}，跳过: {$skipped}");
            Log::info('AD 用户同步完成', ['created' => $created, 'updated' => $updated, 'skipped' => $skipped]);
        }

        return 0;
    }
}
