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
    protected array $systemRoles = ['超级管理员'];

    // 权限分组（中文显示名映射）
    protected array $permGroups = [
        // ITSM 服务管理 (面向公司全员 + IT)
        '工单管理' => ['view tickets', 'manage tickets'],
        '资产管理' => ['view assets', 'manage assets'],
        '知识库'   => ['view knowledge', 'edit knowledge'],
        '变更管理' => ['view changes', 'approve changes'],
        '故障管理' => ['view incidents', 'manage incidents'],
        'SLA 管理' => ['view slas', 'manage slas'],
        // IT 项目管理 (面向 IT 成员)
        '项目管理' => ['create projects', 'edit projects', 'delete projects', 'view projects', 'view all projects', 'assign project members'],
        '分类管理' => ['create categories', 'edit categories', 'delete categories', 'view categories'],
        '附件管理' => ['upload attachments', 'delete attachments'],
        // 系统管理
        '用户管理' => ['create users', 'edit users', 'delete users', 'view users'],
        '系统设置' => ['manage roles'],
    ];

    protected array $permLabels = [
        // ITSM
        'view tickets'          => '查看工单（自己）',
        'manage tickets'        => '管理工单（全部+处理）',
        'view assets'           => '查看资产（自己）',
        'manage assets'         => '管理资产（全部+编辑）',
        'view knowledge'        => '查看知识库',
        'edit knowledge'        => '编辑知识库',
        'view changes'          => '查看变更',
        'approve changes'       => '审批变更',
        'view incidents'        => '查看故障',
        'manage incidents'      => '管理故障（处理+关闭）',
        'view slas'             => '查看 SLA',
        'manage slas'           => '管理 SLA（编辑+删除）',
        // IT 项目管理
        'create projects'       => '创建项目',
        'edit projects'         => '编辑项目',
        'delete projects'       => '删除项目',
        'view projects'         => '查看项目（参与）',
        'view all projects'     => '查看所有项目',
        'assign project members'=> '分配项目成员',
        'create categories'     => '创建分类',
        'edit categories'       => '编辑分类',
        'delete categories'     => '删除分类',
        'view categories'       => '查看分类',
        'upload attachments'    => '上传附件',
        'delete attachments'    => '删除附件',
        // 系统管理
        'create users'          => '创建用户',
        'edit users'            => '编辑用户',
        'delete users'          => '删除用户',
        'view users'            => '查看用户',
        'manage roles'          => '管理角色权限',
    ];

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
        $uniqueRule = $this->isEditing
            ? 'unique:roles,name,' . $this->editingRoleId
            : 'unique:roles,name';

        $this->validate([
            'formName' => "required|string|max:50|{$uniqueRule}",
        ], [
            'formName.required' => '角色名称不能为空',
            'formName.unique'   => '角色名称已存在',
            'formName.max'      => '角色名最长 50 个字符',
        ]);

        if ($this->isEditing) {
            $role = Role::findOrFail($this->editingRoleId);

            // 超级管理员不允许改名
            if (in_array($role->name, $this->systemRoles) && $role->name !== $this->formName) {
                session()->flash('error', '系统保护角色不允许改名。');
                return;
            }

            $role->name        = $this->formName;
            $role->description = $this->formDescription;
            $role->save();
            $role->syncPermissions($this->selectedPerms);
            session()->flash('success', "角色「{$role->name}」已更新。");
        } else {
            $role = Role::create([
                'name'        => $this->formName,
                'guard_name'  => 'web',
                'description' => $this->formDescription,
            ]);
            $role->syncPermissions($this->selectedPerms);
            session()->flash('success', "角色「{$role->name}」已创建。");
        }

        $this->showRoleModal = false;
    }

    // ── 删除确认 ──────────────────────────────────────
    public function confirmDelete(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        if (in_array($role->name, $this->systemRoles)) {
            session()->flash('error', '系统保护角色不允许删除。');
            return;
        }
        $this->deletingRoleId  = $roleId;
        $this->showDeleteModal = true;
    }

    public function deleteRole(): void
    {
        $role = Role::find($this->deletingRoleId);
        if ($role) {
            if (in_array($role->name, $this->systemRoles)) {
                session()->flash('error', '系统保护角色不允许删除。');
            } else {
                $roleName = $role->name;
                $role->delete();
                session()->flash('success', "角色「{$roleName}」已删除。");
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
        ])->layout('layouts.app', ['title' => '角色管理']);
    }
}
