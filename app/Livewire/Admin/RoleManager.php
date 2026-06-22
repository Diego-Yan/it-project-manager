<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManager extends Component
{
    public bool   $showRoleModal   = false;
    public bool   $isEditing       = false;
    public ?int   $editingRoleId   = null;

    public string $formName        = '';
    public string $formDescription = '';
    public array  $selectedPerms   = [];

    public bool   $showDeleteModal = false;
    public ?int   $deletingRoleId  = null;

    // 受保护的系统角色，不允许删除
    protected array $systemRoles = [];

    // 权限分组（中文显示名映射）
    protected array $permGroups = [];

    protected array $permLabels = [];

    // [REVIEW-FIX-R1 #8 P1] 修复 fatal error：原属性声明使用 __() 函数调用作为默认值，
    // 违反 PHP 常量表达式规则，导致角色管理页面加载即崩溃。改为在 boot() 中初始化
    // （boot 在每次 Livewire 请求均执行，protected 属性不会跨请求保留，需每次重建）。
    public function boot(): void
    {
        $this->systemRoles = [__('超级管理员')];

        $this->permGroups = [
            // ITSM 服务管理 (面向公司全员 + IT)
            __('工单管理') => ['view tickets', 'manage tickets'],
            __('资产管理') => ['view assets', 'manage assets'],
            __('知识库')   => ['view knowledge', 'edit knowledge'],
            __('变更管理') => ['view changes', 'approve changes'],
            __('故障管理') => ['view incidents', 'manage incidents'],
            __('SLA 管理') => ['view slas', 'manage slas'],
            // IT 项目管理 (面向 IT 成员)
            __('项目管理') => ['create projects', 'edit projects', 'delete projects', 'view projects', 'view all projects', 'assign project members'],
            __('分类管理') => ['create categories', 'edit categories', 'delete categories', 'view categories'],
            __('附件管理') => ['upload attachments', 'delete attachments'],
            // 系统管理
            __('用户管理') => ['create users', 'edit users', 'delete users', 'view users'],
            __('系统设置') => ['manage roles'],
        ];

        $this->permLabels = [
            // ITSM
            'view tickets'          => __('查看工单（自己）'),
            'manage tickets'        => __('管理工单（全部+处理）'),
            'view assets'           => __('查看资产（自己）'),
            'manage assets'         => __('管理资产（全部+编辑）'),
            'view knowledge'        => __('查看知识库'),
            'edit knowledge'        => __('编辑知识库'),
            'view changes'          => __('查看变更'),
            'approve changes'       => __('审批变更'),
            'view incidents'        => __('查看故障'),
            'manage incidents'      => __('管理故障（处理+关闭）'),
            'view slas'             => __('查看 SLA'),
            'manage slas'           => __('管理 SLA（编辑+删除）'),
            // IT 项目管理
            'create projects'       => __('创建项目'),
            'edit projects'         => __('编辑项目'),
            'delete projects'       => __('删除项目'),
            'view projects'         => __('查看项目（参与）'),
            'view all projects'     => __('查看所有项目'),
            'assign project members'=> __('分配项目成员'),
            'create categories'     => __('创建分类'),
            'edit categories'       => __('编辑分类'),
            'delete categories'     => __('删除分类'),
            'view categories'       => __('查看分类'),
            'upload attachments'    => __('上传附件'),
            'delete attachments'    => __('删除附件'),
            // 系统管理
            'create users'          => __('创建用户'),
            'edit users'            => __('编辑用户'),
            'delete users'          => __('删除用户'),
            'view users'            => __('查看用户'),
            'manage roles'          => __('管理角色权限'),
        ];
    }

    // ── 打开新建 ──────────────────────────────────────
    public function openCreateModal(): void
    {
        $this->reset(['formName', 'formDescription', 'selectedPerms', 'isEditing', 'editingRoleId']);
        $this->showRoleModal = true;
    }

    // ── 打开编辑 ──────────────────────────────────────
    public function openEditModal(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->isEditing       = true;
        $this->editingRoleId   = $roleId;
        $this->formName        = $role->name;
        $this->formDescription = $role->description ?? '';
        $this->selectedPerms   = $role->permissions->pluck('name')->toArray();
        $this->showRoleModal   = true;
    }

    // ── 保存角色 ──────────────────────────────────────
    public function saveRole(): void
    {
        // [REVIEW-FIX] R12.7: 角色管理操作需权限检查
        if (!auth()->user()->can('manage roles')) {
            session()->flash('error', __('没有角色管理权限'));
            return;
        }
        $uniqueRule = $this->isEditing
            ? 'unique:roles,name,' . $this->editingRoleId
            : 'unique:roles,name';

        $this->validate([
            'formName' => "required|string|max:50|{$uniqueRule}",
        ], [
            'formName.required' => __('角色名称不能为空'),
            'formName.unique'   => __('角色名称已存在'),
            'formName.max'      => __('角色名最长 50 个字符'),
        ]);

        if ($this->isEditing) {
            $role = Role::findOrFail($this->editingRoleId);

            // 超级管理员不允许改名
            if (in_array($role->name, $this->systemRoles) && $role->name !== $this->formName) {
                session()->flash('error', __('系统保护角色不允许改名。'));
                return;
            }

            $role->name        = $this->formName;
            $role->description = $this->formDescription;
            $role->save();
            $role->syncPermissions($this->selectedPerms);
            session()->flash('success', __('角色「:name」已更新。', ['name' => $role->name]));
        } else {
            $role = Role::create([
                'name'        => $this->formName,
                'guard_name'  => 'web',
                'description' => $this->formDescription,
            ]);
            $role->syncPermissions($this->selectedPerms);
            session()->flash('success', __('角色「:name」已创建。', ['name' => $role->name]));
        }

        $this->showRoleModal = false;
    }

    // ── 删除确认 ──────────────────────────────────────
    public function confirmDelete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        if (in_array($role->name, $this->systemRoles)) {
            session()->flash('error', __('系统保护角色不允许删除。'));
            return;
        }
        $this->deletingRoleId  = $roleId;
        $this->showDeleteModal = true;
    }

    // [REVIEW-FIX] M6: 删除前检查角色是否仍有用户在使用
    public function deleteRole(): void
    {
        if (!auth()->user()->can('manage roles')) {
            session()->flash('error', __('没有角色管理权限'));
            return;
        }
        $role = Role::withCount('users')->find($this->deletingRoleId);
        if ($role) {
            if (in_array($role->name, $this->systemRoles)) {
                session()->flash('error', __('系统保护角色不允许删除。'));
            } elseif ($role->users_count > 0) {
                session()->flash('error', __('角色「:name」仍有 :count 名用户在使用，请先移除用户后再删除。', ['name' => $role->name, 'count' => $role->users_count]));
            } else {
                $roleName = $role->name;
                $role->delete();
                session()->flash('success', __('角色「:name」已删除。', ['name' => $roleName]));
            }
        }
        $this->showDeleteModal = false;
        $this->deletingRoleId  = null;
    }

    // ── 全选/取消某分组 ──────────────────────────────
    public function toggleGroup(string $group): void
    {
        $groupPerms = $this->permGroups[$group] ?? [];
        $allSelected = count(array_intersect($groupPerms, $this->selectedPerms)) === count($groupPerms);

        if ($allSelected) {
            $this->selectedPerms = array_values(array_diff($this->selectedPerms, $groupPerms));
        } else {
            $this->selectedPerms = array_values(array_unique(array_merge($this->selectedPerms, $groupPerms)));
        }
    }

    public function render()
    {
        return view('livewire.admin.role-manager', [
            'roles'       => Role::withCount('users')->orderBy('name')->get(),
            'permGroups'  => $this->permGroups,
            'permLabels'  => $this->permLabels,
        ])->layout('layouts.app', ['title' => __('角色管理')]);
    }
}
