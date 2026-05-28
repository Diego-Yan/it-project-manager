# IT 运维项目管理系统 - 版本 V1.1

**发布日期**: 2026-03-23
**版本类型**: 功能增强

---

## 版本变更

### 新增功能
- ✅ Windows AD 域认证集成
- ✅ 双模式登录（本地账号 / AD 域账号）
- ✅ 自动用户同步与创建
- ✅ AD 失败后本地认证回退
- ✅ 登录失败锁定机制

### 技术改进
- ✅ 新增 `LdapAuthService` 服务类
- ✅ 新增 `ad-auth.php` 配置文件
- ✅ 数据库新增 AD 相关字段
- ✅ 登录页面重构（支持切换）

### 依赖更新
- ✅ 新增 `php-ldap` 扩展

---

## 快速开始

### 访问地址
- **登录页**: http://172.24.150.122:8000/login
- **仪表盘**: http://172.24.150.122:8000/dashboard

### 默认账号
- **管理员**: `admin` / `Admin@2024!`
- **演示账号**: `demo` / `Demo@2024!`

### AD 配置（可选）
如需使用 AD 认证，请编辑 `/var/www/it-project-manager/.env`：

```env
AD_AUTH_ENABLED=true
AD_SERVER=ldap://your-ad-server.domain.com
AD_DOMAIN=domain.com
AD_BASE_DN=DC=domain,DC=com
```

详细配置请参考 `UPGRADE_TO_V1.1.md`

---

## 系统状态

### 服务状态
- **Systemd 服务**: ✅ Active (running)
- **HTTP 响应**: ✅ 200 OK
- **内存占用**: ~70MB

### 数据库
- **Projects**: 0
- **Users**: 2
- **Categories**: 7

### 备份信息
- **V1.0 备份**: `/root/backups/it-project-manager/`
- **备份时间**: 2026-03-23 12:17:15

---

## 技术栈

- **后端**: Laravel 12.0
- **前端**: Livewire 3.5 + FluxUI
- **数据库**: MySQL 8.0
- **认证**: Laravel Auth + Spatie Permission + LDAP
- **缓存**: File + Redis（可选）

---

## 文档

- [升级文档 V1.1](./UPGRADE_TO_V1.1.md)
- [项目结构](./PROJECT_STRUCTURE.md)
- [快速开始](./README.md)

---

## 问题反馈

如有问题或建议，请联系系统管理员。
