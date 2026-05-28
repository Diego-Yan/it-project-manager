<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. 创建权限 ──────────────────────────────────────
        $permissions = [
            // 用户管理
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',
            // 分类管理
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            // 项目管理
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'assign project members',
            'view all projects',
            // 附件管理
            'upload attachments',
            'delete attachments',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── 2. 创建角色并分配权限 ────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => '超级管理员', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => '管理员', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view users', 'create users', 'edit users',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view projects', 'create projects', 'edit projects', 'delete projects',
            'assign project members', 'view all projects',
            'upload attachments', 'delete attachments',
        ]);

        $projectManager = Role::firstOrCreate(['name' => '项目经理', 'guard_name' => 'web']);
        $projectManager->syncPermissions([
            'view projects', 'create projects', 'edit projects',
            'assign project members', 'view all projects',
            'upload attachments',
        ]);

        $deptLead = Role::firstOrCreate(['name' => '部门主管', 'guard_name' => 'web']);
        $deptLead->syncPermissions([
            'view projects', 'create projects', 'view all projects',
            'upload attachments',
        ]);

        $member = Role::firstOrCreate(['name' => '普通成员', 'guard_name' => 'web']);
        $member->syncPermissions([
            'view projects', 'upload attachments',
        ]);

        // ── 3. 创建默认管理员账号 ────────────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@itops.local'],
            [
                'name'     => '系统管理员',
                'username' => 'admin',
                'password' => Hash::make('Admin@2024!'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('超级管理员');

        // ── 4. 创建演示账号 ──────────────────────────────────
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@itops.local'],
            [
                'name'       => '演示用户',
                'username'   => 'demo',
                'password'   => Hash::make('Demo@2024!'),
                'department' => 'IT 运维部',
                'is_active'  => true,
            ]
        );
        $demoUser->assignRole('项目经理');

        // ── 5. 创建项目分类 ──────────────────────────────────
        $categories = [
            ['name' => '桌面运维',     'color' => 'sky',    'icon' => 'monitor',       'description' => '终端设备管理、软件部署、用户支持'],
            ['name' => '网络运维',     'color' => 'blue',   'icon' => 'network',        'description' => '网络设备配置、链路管理、安全策略'],
            ['name' => '服务器运维',   'color' => 'violet', 'icon' => 'server',         'description' => '服务器部署、性能优化、故障处理'],
            ['name' => '数据中心管理', 'color' => 'indigo', 'icon' => 'building',       'description' => '数据中心基础设施、机柜管理、电力冷却'],
            ['name' => '弱电系统工程', 'color' => 'amber',  'icon' => 'zap',            'description' => '综合布线、门禁、视频监控等弱电项目'],
            ['name' => '信息安全',     'color' => 'red',    'icon' => 'shield',         'description' => '安全审计、漏洞修复、合规管理'],
            ['name' => '系统开发',     'color' => 'green',  'icon' => 'code',           'description' => '内部系统开发、流程自动化、工具开发'],
        ];

        foreach ($categories as $i => $cat) {
            ProjectCategory::firstOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['sort_order' => $i + 1])
            );
        }

        $this->command->info('✅ 初始数据创建完成！');
        $this->command->info('   管理员账号: admin / Admin@2024!');
        $this->command->info('   演示账号:   demo  / Demo@2024!');
    }
}
