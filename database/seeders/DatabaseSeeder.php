<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProjectCategory;
use App\Models\Region;
use App\Models\Sla;
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
            // ITSM — 工单
            'view tickets', 'manage tickets',
            // ITSM — 资产
            'view assets', 'manage assets',
            // ITSM — 知识库
            'view knowledge', 'edit knowledge',
            // ITSM — 变更
            'view changes', 'approve changes',
            // ITSM — 故障
            'view incidents', 'manage incidents',
            // ITSM — SLA
            'view slas', 'manage slas',
            // IT 项目管理
            'view projects', 'create projects', 'edit projects', 'delete projects',
            'assign project members', 'view all projects',
            // 分类 & 附件
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'upload attachments', 'delete attachments',
            // 系统管理
            'view users', 'create users', 'edit users', 'delete users',
            'manage roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── 2. 创建角色 —— 两维度：ITSM + IT项目管理 ────────

        // 超级管理员：全部权限
        $superAdmin = Role::firstOrCreate(['name' => '超级管理员', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // IT 主管：ITSM全部 + 项目管理全部 + 查看用户
        $itManager = Role::firstOrCreate(['name' => 'IT 主管', 'guard_name' => 'web']);
        $itManager->syncPermissions([
            'view tickets', 'manage tickets',
            'view assets', 'manage assets',
            'view knowledge', 'edit knowledge',
            'view changes', 'approve changes',
            'view incidents', 'manage incidents',
            'view slas', 'manage slas',
            'view projects', 'create projects', 'edit projects', 'delete projects',
            'assign project members', 'view all projects',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'upload attachments', 'delete attachments',
            'view users',
        ]);

        // IT 工程师：工单处理 + 资产查看 + 知识编辑 + 项目管理(成员视角)
        $itEngineer = Role::firstOrCreate(['name' => 'IT 工程师', 'guard_name' => 'web']);
        $itEngineer->syncPermissions([
            'view tickets', 'manage tickets',
            'view assets', 'manage assets',
            'view knowledge', 'edit knowledge',
            'view changes',
            'view incidents',
            'view slas',
            'view projects', 'create projects',
            'upload attachments',
        ]);

        // 部门主管：查看本部门 + 知识库 + 项目管理(查看全部)
        $deptLead = Role::firstOrCreate(['name' => '部门主管', 'guard_name' => 'web']);
        $deptLead->syncPermissions([
            'view tickets',
            'view assets',
            'view knowledge',
            'view changes',
            'view incidents',
            'view slas',
            'view projects', 'create projects', 'view all projects',
            'upload attachments', 'view categories',
        ]);

        // 普通员工：只看自己的工单/资产 + 知识库
        $member = Role::firstOrCreate(['name' => '普通员工', 'guard_name' => 'web']);
        $member->syncPermissions([
            'view tickets',
            'view assets',
            'view knowledge',
            'view projects',
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
        $demoUser->assignRole('IT 工程师');

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

        // ── 6. 创建地区 ──────────────────────────────────────
        // [REVIEW-FIX-R4 #2 P2] 补充缺失的 Region Seeder：
        // 原 Seeder 未创建地区数据，但 TicketBoard/MyTickets 的 formRegionId 验证规则为
        // 'required|exists:regions,id' → 新部署的系统工单表单无法提交（无地区可选）。
        // 补充杭州/深圳双地区，与 yanmade.com 实际两地办公一致。
        $regions = [
            ['name' => '杭州', 'sort_order' => 1],
            ['name' => '深圳', 'sort_order' => 2],
        ];
        foreach ($regions as $region) {
            Region::firstOrCreate(['name' => $region['name']], $region);
        }

        // ── 7. 创建 SLA 初始数据 ─────────────────────────────
        // [REVIEW-FIX-R4 #3 P1] 补充缺失的 SLA Seeder：
        // 原 Seeder 未创建 SLA 数据，Sla::getDeadline() 查询无匹配记录时返回 null，
        // 导致所有工单的 sla_deadline 为 null → SLA 违约检测 (isSlaBreached) 永远为 false，
        // 工单超时监控完全失效。补充 4 个优先级的默认 SLA 配置。
        $slas = [
            ['name' => '低优先级 SLA', 'priority' => 'low',      'response_minutes' => 480,  'resolution_minutes' => 2880, 'is_active' => true],  // 响应8h, 解决2天
            ['name' => '中优先级 SLA', 'priority' => 'medium',   'response_minutes' => 240,  'resolution_minutes' => 1440, 'is_active' => true],  // 响应4h, 解决1天
            ['name' => '高优先级 SLA', 'priority' => 'high',     'response_minutes' => 60,   'resolution_minutes' => 480,  'is_active' => true],  // 响应1h, 解决8h
            ['name' => '紧急 SLA',     'priority' => 'critical', 'response_minutes' => 15,   'resolution_minutes' => 120,  'is_active' => true],  // 响应15min, 解决2h
        ];
        foreach ($slas as $sla) {
            Sla::firstOrCreate(['priority' => $sla['priority']], $sla);
        }

        $this->command->info('✅ 初始数据创建完成！');
        $this->command->info('   管理员账号: admin / Admin@2024!');
        $this->command->info('   演示账号:   demo  / Demo@2024!');
    }
}
