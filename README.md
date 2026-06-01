# IT 服务管理系统

IT 运维团队内部项目管理系统，支持项目进度跟踪、成员协作、操作审计和 Windows AD 域认证。

## 技术栈

- **后端**: Laravel 12.x / PHP 8.2+
- **前端**: Livewire 3.5 + FluxUI (Tailwind CSS)
- **数据库**: MySQL 8.0 / SQLite
- **认证**: Laravel Auth + Spatie Permission RBAC + LDAP/AD

## 功能概览

- **仪表盘** — 项目统计、到期预警、分类分布、操作动态
- **项目管理** — CRUD、进度追踪、多成员分配、附件上传
- **权限体系** — 5 个预设角色（超级管理员/管理员/项目经理/部门主管/普通成员）、17 个细粒度权限
- **AD 域集成** — 双模式登录、自动用户同步、失败锁定、在线配置与连接测试
- **操作审计** — 全量 JSON 变更日志
- **项目分类** — 运维/开发两大类型，7 个预设分类

## 本地开发

```bash
git clone https://github.com/Diego-Yan/it-project-manager.git
cd it-project-manager

cp .env.example .env
# 编辑 .env：设置数据库连接（默认 SQLite 可直接使用）

composer install
npm install && npm run build

php artisan key:generate
php artisan migrate --seed

php artisan serve
# 访问 http://localhost:8000
```

## ⚠️ 安全提示

**首次登录后请立即修改默认密码！**

## 默认账号

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 超级管理员 | admin | Admin@2024! |
| 演示账号 | demo | Demo@2024! |

## AD 域配置（可选）

在 `.env` 中配置以下参数：

```env
AD_AUTH_ENABLED=true
AD_SERVER=ldap://your-ad-server.domain.com
AD_PORT=389
AD_DOMAIN=domain.com
AD_BASE_DN=DC=domain,DC=com
AD_ADMIN_USERNAME=admin
AD_ADMIN_PASSWORD=your_password
```

详细配置参考 [UPGRADE_TO_V1.1.md](./UPGRADE_TO_V1.1.md)。

## 目录结构

```
app/
├── Console/Commands/    # ad:sync-users 同步命令
├── Http/Controllers/    # 登录控制器
├── Livewire/            # Livewire 全栈组件
│   ├── Admin/           #   用户管理、角色管理、AD配置
│   ├── Categories/      #   分类管理
│   └── Projects/        #   项目列表、表单、详情
├── Models/              # Eloquent 模型
└── Services/            # LdapAuthService
```

## License

MIT
