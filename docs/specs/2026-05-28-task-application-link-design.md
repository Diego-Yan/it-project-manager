# 项目任务、申请、关联 — 设计规格

**日期**: 2026-05-28
**版本**: V1.3.0

---

## 模块一：子任务系统

### 数据模型

```sql
tasks
├── project_id        FK → projects
├── title             (required, max 200)
├── description       (nullable)
├── assigned_to       FK → users (nullable, null = 可认领)
├── created_by        FK → users
├── status            enum: pending_confirmation | in_progress | completed
├── priority          enum: not_urgent | normal | urgent (default: normal)
├── due_date          (nullable)
├── confirmed_at      (nullable timestamp)
├── completed_at      (nullable timestamp)
└── timestamps
```

### 状态流转

```
                  ┌── 拒绝/取消 ──→ assigned_to=null, status=in_progress → 可被他人认领
                  ↓
[分配] → pending_confirmation → 确认 → in_progress → 完成 → completed
                                                      ↓
                                    未分配的任务 → 成员可认领 → in_progress
```

### 业务规则

- 任务创建后立即可见（A 方案）
- 被分配人确认后状态从 `pending_confirmation` → `in_progress`
- `assigned_to = null` 的任务显示「认领」按钮，任何项目成员可认领
- 认领后直接进入 `in_progress`（无需审批，C 方案）
- 项目进度独立于任务（C 方案：辅助显示 "已完成 2/4"）

---

## 模块二：项目申请系统

### 数据模型

```sql
project_applications
├── project_id        FK → projects
├── user_id           FK → users
├── message           (nullable, 申请理由)
├── status            enum: pending | approved | rejected
└── timestamps
```

### 业务流程

1. 项目列表对所有登录用户可见
2. 已完成 / 已加入的项目正常显示
3. 其他项目显示「申请加入」按钮
4. 项目负责人/管理员看到申请列表，可批准/拒绝
5. 批准后用户自动成为项目成员（role: member）

---

## 模块三：项目关联

### 数据模型

```sql
project_links
├── project_id        FK → projects (源项目)
├── target_id         FK → projects (目标项目)
├── link_type         enum: blocks | relates_to | parent
├── created_by        FK → users
└── timestamps
```

### 三种关联

| 类型 | 方向 | 行为 |
|------|------|------|
| `blocks` | A → B | A 必须 completed 后 B 才能开始。B 详情页显示等待提示 |
| `relates_to` | A ↔ B | 双向可见引用 |
| `parent` | 父 ← 子 | 父汇总子任务进度；一个项目最多一个父 |

### 约束

- 不能链接自身
- 同类型 A↔B 不可重复
- 父子：一个项目最多一个父
- 阻断：不允许循环依赖（A→B→C→A 检出并拒绝）

---

## 仪表盘更新

- 新增「我的待确认任务」卡片
- 项目统计区增加「任务完成率」辅助显示
- 「我的待办」：需要我确认的任务 + 待审批的加入申请

---

## 不包含（V1.3 范围外）

- 邮件/站内通知
- 任务评论
- 批量分配
- 甘特图/看板视图
- 项目关联的可视化拓扑图
