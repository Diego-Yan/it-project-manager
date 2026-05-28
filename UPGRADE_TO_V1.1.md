# IT 运维项目管理系统 V1.1 升级文档

## 版本信息

- **版本号**: V1.1
- **发布日期**: 2026-03-23
- **升级类型**: 功能增强

---

## 新增功能

### 1. Windows AD 域认证集成

#### 功能描述
支持用户通过 Windows Active Directory (AD) 域账号登录系统，同时保留本地账号登录作为备选方案。

#### 主要特性

1. **双模式登录**
   - 本地账号登录（原有功能）
   - AD 域账号登录（新增）
   - 一键切换登录模式

2. **自动用户同步**
   - 首次 AD 登录时自动创建本地用户
   - 自动同步 AD 用户信息（显示名称、邮箱、部门等）
   - 支持自动分配默认角色

3. **安全增强**
   - 失败尝试锁定（默认 5 次后锁定 30 分钟）
   - 支持回退到本地认证（可选）
   - TLS/SSL 加密连接支持

4. **灵活配置**
   - 可配置 AD 服务器地址、端口、域名
   - 可禁用 AD 认证，仅使用本地账号
   - 支持管理员账号查询用户（可选）

---

## 技术实现

### 数据库变更

新增表字段（`users` 表）：

| 字段 | 类型 | 说明 |
|------|------|------|
| ad_domain | varchar(255) | AD 域名 |
| ad_username | varchar(255) | AD 用户名 |
| ad_display_name | varchar(255) | AD 显示名称 |
| ad_email | varchar(255) | AD 邮箱 |
| ad_authenticated | tinyint(1) | 是否为 AD 认证用户 |
| ad_last_sync_at | timestamp | AD 最后同步时间 |

索引：`idx_ad_user` (ad_domain, ad_username)

### 新增文件

1. **服务类**
   - `app/Services/LdapAuthService.php` - AD 认证服务

2. **配置文件**
   - `config/ad-auth.php` - AD 认证配置

3. **更新文件**
   - `app/Http/Controllers/LoginController.php` - 登录控制器
   - `resources/views/auth/login.blade.php` - 登录页面

---

## 环境配置

### .env 新增配置项

```env
# AD 认证配置 (V1.1)
AD_AUTH_ENABLED=true                    # 是否启用 AD 认证
AD_SERVER=ldap://your-ad-server.domain.com  # AD 服务器地址
AD_PORT=389                            # AD 端口
AD_USE_TLS=false                         # 是否启用 TLS
AD_USE_SSL=false                         # 是否启用 SSL
AD_DOMAIN=domain.com                    # AD 域名
AD_BASE_DN=DC=domain,DC=com            # AD 基础 DN
AD_ADMIN_USERNAME=                      # AD 管理员用户名（可选）
AD_ADMIN_PASSWORD=                      # AD 管理员密码（可选）
AD_AUTO_CREATE_USER=true                # 自动创建本地用户
AD_AUTO_SYNC_GROUPS=false               # 自动同步用户组
AD_DEFAULT_ROLE=user                    # 默认分配角色
AD_FALLBACK_TO_LOCAL=true              # AD 失败后回退到本地认证
AD_LOCK_AFTER_FAILED=5                 # 失败锁定次数
AD_LOCK_MINUTES=30                     # 锁定时长（分钟）
```

---

## 使用说明

### 1. 配置 AD 服务器

编辑 `/var/www/it-project-manager/.env`，更新以下参数：

```env
AD_SERVER=ldap://ad-server.company.com
AD_DOMAIN=company.com
AD_BASE_DN=DC=company,DC=com
AD_ADMIN_USERNAME=admin
AD_ADMIN_PASSWORD=your_password
```

### 2. 测试 AD 连接

在项目目录执行：

```bash
cd /var/www/it-project-manager
php artisan tinker
>>> \App\Services\LdapAuthService::connect()
```

### 3. 用户登录

1. 访问 `http://your-server:8000/login`
2. 选择"AD 域账号"标签
3. 输入 AD 用户名和密码
4. 点击"AD 域登录"

首次登录时，系统会自动创建本地用户记录。

### 4. 混合使用

AD 用户和本地用户可以同时存在，用户可以选择使用哪种方式登录。

---

## 依赖要求

### PHP 扩展

- **php-ldap** - LDAP 认证核心扩展

安装方式：

```bash
apt-get install php8.2-ldap
systemctl restart php-fpm
```

---

## 备份与恢复

### 备份 V1.0 版本

升级前已自动备份至：

- `/root/backups/it-project-manager/it-project-manager_full_v1.0_20260323_121715.tar.gz`
- `/root/backups/it-project-manager/itops_pm_v1.0_20260323_121715.sql.gz`

### 回滚到 V1.0

如需回滚到 V1.0 版本：

```bash
# 停止服务
systemctl stop it-project-manager

# 恢复数据库
cd /root/backups/it-project-manager
gunzip itops_pm_v1.0_20260323_121715.sql.gz
mysql -u root -p itops_pm < itops_pm_v1.0_20260323_121715.sql

# 恢复代码
rm -rf /var/www/it-project-manager
tar -xzf it-project-manager_code_v1.0_20260323_121715.tar.gz -C /var/www

# 重启服务
systemctl start it-project-manager
```

---

## 已知问题

无

---

## 后续计划

- 支持 LDAP 用户组同步
- 支持 LDAP 密码修改
- 支持 LDAP 属性自定义映射
- 添加 AD 连接测试页面

---

## 版本兼容性

- Laravel: ^12.0
- PHP: >= 8.2
- MySQL: >= 8.0

---

## 技术支持

如有问题，请联系系统管理员。
