<?php

namespace App\Livewire\Admin;

use App\Models\ProjectCategory;
use App\Models\User;
use App\Services\LdapAuthService;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserManager extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterSource  = '';  // '', 'local', 'ad'
    public string $filterRole    = '';

    // 创建/编辑用户 Modal
    public bool   $showUserModal  = false;
    public bool   $isEditing      = false;
    public ?int   $editingUserId  = null;
    public bool   $isAdUser       = false;  // 当前编辑的是否为域账号（只读保护）

    // 新建时的账号类型选择：'local' | 'ad'
    public string $createType     = 'local';

    // AD 搜索相关
    public string $adSearchKeyword  = '';
    public array  $adSearchResults  = [];
    public bool   $adSearching      = false;
    public string $adSelectedUser   = '';  // 已选中的 sAMAccountName

    // 表单字段
    public string $formName       = '';
    public string $formUsername   = '';
    public string $formEmail      = ''  ;
    public string $formPassword   = '';
    public string $formDepartment = '';
    public string $formPhone      = '';
    public string $formRole       = '';
    public bool   $formIsActive   = true;
    public array  $formExpertiseCategories = [];

    // 删除确认
    public bool   $showDeleteModal = false;
    public ?int   $deletingUserId  = null;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterSource(): void { $this->resetPage(); }
    public function updatingFilterRole(): void   { $this->resetPage(); }

    // ── 打开新建 Modal ────────────────────────────────
    public function openCreateModal(): void
    {
        $this->reset([
            'formName','formUsername','formEmail','formPassword',
            'formDepartment','formPhone','formRole',
            'formExpertiseCategories',
            'isEditing','editingUserId','isAdUser',
            'createType','adSearchKeyword','adSearchResults','adSearching','adSelectedUser',
        ]);
        $this->createType    = 'local';
        $this->formIsActive  = true;
        $this->showUserModal = true;
    }

    // ── 切换新建类型 ──────────────────────────────────
    public function switchCreateType(string $type): void
    {
        $this->createType = $type;
        // 重置表单
        $this->reset([
            'formName','formUsername','formEmail','formPassword',
            'formDepartment','formPhone','formRole',
            'formExpertiseCategories',
            'adSearchKeyword','adSearchResults','adSearching','adSelectedUser',
        ]);
        $this->formIsActive = true;
    }

    // ── AD 实时搜索（防抖在视图端） ───────────────────
    public function updatedAdSearchKeyword(): void
    {
        $keyword = trim($this->adSearchKeyword);
        $this->adSelectedUser  = '';
        $this->adSearchResults = [];

        if (mb_strlen($keyword) < 2) {
            return;
        }

        $this->adSearching = true;

        try {
            $ldap = new LdapAuthService();
            $this->adSearchResults = $ldap->searchUsers($keyword, 15);
        } catch (\Throwable $e) {
            // [REVIEW-FIX] SP12.7: 记录 AD 搜索失败日志
            \Illuminate\Support\Facades\Log::warning("AD search failed for keyword: {$keyword}", ['error' => $e->getMessage()]);
            $this->adSearchResults = [];
        }

        $this->adSearching = false;
    }

    // ── 选中 AD 搜索结果，带出用户信息 ───────────────
    public function selectAdUser(string $username): void
    {
        $this->adSelectedUser   = $username;
        $this->adSearchKeyword  = $username;
        $this->adSearchResults  = [];  // 关闭下拉

        // 从结果里找或重新查
        try {
            $ldap = new LdapAuthService();
            $info = $ldap->getUserInfoByUsername($username);

            if ($info) {
                $this->formUsername   = $info['username'];
                $this->formName       = $info['name'];
                $this->formEmail      = $info['email'];
                $this->formDepartment = $info['department'];
                $this->formPhone      = $info['phone'];
            }
        } catch (\Throwable $e) {
            // [REVIEW-FIX] SP12.6: 记录 AD 用户详情获取失败日志
            \Illuminate\Support\Facades\Log::warning("AD user detail fetch failed for: {$username}", ['error' => $e->getMessage()]);
        }
    }

    // ── 打开编辑 Modal ────────────────────────────────
    public function openEditModal(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->isEditing     = true;
        $this->editingUserId = $userId;
        $this->isAdUser      = (bool) $user->ad_authenticated;

        $this->formName       = $user->name;
        $this->formUsername   = $user->username ?? '';
        $this->formEmail      = $user->email ?? '';
        $this->formPassword   = '';  // 留空表示不修改
        $this->formDepartment = $user->department ?? '';
        $this->formPhone      = $user->phone ?? '';
        $this->formRole       = $user->roles->first()?->name ?? '';
        $this->formExpertiseCategories = $user->expertiseCategories()->pluck('category_id')->toArray();
        $this->formIsActive   = (bool) $user->is_active;

        $this->showUserModal = true;
    }

    // ── 保存用户 ───────────────────────────────────────
    public function saveUser(): void
    {
        // [REVIEW-FIX-R1 #1 P1] 权限提升修复：创建用户必须具备 create users 权限。
        // 原代码仅在校验编辑分支检查 edit users，AD/本地创建分支均未校验 create users，
        // 导致仅有 view users（只读）权限的用户（如 IT 主管角色）可越权创建任意用户。
        if ($this->isEditing) {
            if (!auth()->user()->can('edit users')) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('create users')) {
                abort(403);
            }
        }
        // 域账号只允许修改角色和部门
        if ($this->isEditing && $this->isAdUser) {
            $rules = [
                'formRole'       => 'nullable|string',
                'formDepartment' => 'nullable|string|max:100',
                'formIsActive'   => 'boolean',
            ];
        } elseif ($this->createType === 'ad' && !$this->isEditing) {
            // 新建 AD 账号：必须已选中用户
            if (empty($this->adSelectedUser)) {
                $this->addError('adSearchKeyword', __('请先从搜索结果中选择一个 AD 用户'));
                return;
            }
            $rules = [
                'formUsername'   => 'required|string',
                'formName'       => 'required|string|max:100',
                'formEmail'      => 'nullable|email|max:255',
                'formRole'       => 'nullable|string',
                'formDepartment' => 'nullable|string|max:100',
                'formIsActive'   => 'boolean',
            ];
        } else {
            // 本地用户
            $rules = [
                'formName'       => 'required|string|max:100',
                'formUsername'   => 'required|string|max:50|unique:users,username' . ($this->isEditing ? ",{$this->editingUserId}" : ''),
                'formEmail'      => 'nullable|email|max:255',
                // [REVIEW-FIX] M4: 密码需至少8位，含至少一个字母和一个数字
                'formPassword'   => $this->isEditing ? 'nullable|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d).{8,}$/' : 'required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d).{8,}$/',
                'formDepartment' => 'nullable|string|max:100',
                'formPhone'      => 'nullable|string|max:20',
                'formRole'       => 'nullable|string',
                'formIsActive'   => 'boolean',
            ];
        }

        $this->validate($rules);

        if ($this->isEditing) {
            $user = User::findOrFail($this->editingUserId);

            if ($this->isAdUser) {
                // 域账号：只更新允许的字段
                $user->update([
                    'department' => $this->formDepartment,
                    'is_active'  => $this->formIsActive,
                ]);
            } else {
                $data = [
                    'name'       => $this->formName,
                    'username'   => $this->formUsername,
                    'email'      => $this->formEmail ?: null,
                    'department' => $this->formDepartment,
                    'phone'      => $this->formPhone,
                    'is_active'  => $this->formIsActive,
                ];
                if (!empty($this->formPassword)) {
                    $data['password'] = Hash::make($this->formPassword);
                }
                $user->update($data);
            }

            // [REVIEW-FIX] H3: 角色和类别独立同步，避免角色为空时类别被跳过
            if ($this->formRole) {
                $user->syncRoles([$this->formRole]);
            }
            $user->expertiseCategories()->sync($this->formExpertiseCategories);

            session()->flash('success', __('用户信息已更新。'));

        } elseif ($this->createType === 'ad') {
            // 检查是否已存在
            // [REVIEW-FIX] L4: 嵌套 where 防止 orWhere 误匹配无关用户
            $existing = User::where(function ($q) {
                $q->where('ad_username', $this->formUsername)
                  ->orWhere('username', $this->formUsername);
            })->first();

            if ($existing) {
                $this->addError('adSearchKeyword', __('该 AD 账号已存在于系统中（:name）', ['name' => $existing->name]));
                return;
            }

            $user = User::create([
                'name'             => $this->formName,
                'username'         => $this->formUsername,
                // [REVIEW-FIX] SP7.1: 移除硬编码公司域名，未配置时留空
                'email'            => $this->formEmail ?: ($this->formUsername . '@' . config('ad-auth.domain', '')),
                'password'         => Hash::make(\Illuminate\Support\Str::random(32)),
                'department'       => $this->formDepartment,
                'phone'            => $this->formPhone,
                'is_active'        => $this->formIsActive,
                'ad_authenticated' => true,
                'ad_username'      => $this->formUsername,
                // [REVIEW-FIX] SP7.1: 移除硬编码公司域名
                'ad_domain'        => config('ad-auth.domain', ''),
                'ad_display_name'  => $this->formName,
                'ad_email'         => $this->formEmail,
                'ad_last_sync_at'  => now(),
            ]);

            if ($this->formRole) {
                $user->assignRole($this->formRole);
            }
            $user->expertiseCategories()->sync($this->formExpertiseCategories);

            session()->flash('success', __('AD 域账号已添加：:name', ['name' => $this->formName]));

        } else {
            // 本地用户
            $user = User::create([
                'name'             => $this->formName,
                'username'         => $this->formUsername,
                'email'            => $this->formEmail ?: null,
                'password'         => Hash::make($this->formPassword),
                'department'       => $this->formDepartment,
                'phone'            => $this->formPhone,
                'is_active'        => $this->formIsActive,
                'ad_authenticated' => false,
            ]);

            if ($this->formRole) {
                $user->assignRole($this->formRole);
            }
            $user->expertiseCategories()->sync($this->formExpertiseCategories);

            session()->flash('success', __('本地用户创建成功。'));
        }

        $this->showUserModal = false;
        $this->reset(['adSearchKeyword','adSearchResults','adSelectedUser','createType']);
    }

    // ── 切换用户状态 ───────────────────────────────────
    public function toggleActive(int $userId): void
    {
        if (!auth()->user()->can('edit users')) abort(403);
        $user = User::findOrFail($userId);
        if ($user->id === auth()->id()) {
            session()->flash('error', __('不能禁用自己。'));
            return;
        }
        $user->is_active = !$user->is_active;
        $user->save();
        session()->flash('success', $user->is_active ? __('用户已启用。') : __('用户已禁用。'));
    }

    // ── 删除确认弹窗 ───────────────────────────────────
    public function confirmDelete(int $userId): void
    {
        $this->deletingUserId = $userId;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        if (!auth()->user()->can('delete users')) abort(403);

        $user = User::find($this->deletingUserId);

        if ($user) {
            if ($user->id === auth()->id()) {
                session()->flash('error', __('不能删除当前登录账号。'));
                $this->showDeleteModal = false;
                return;
            }
            // [REVIEW-FIX] I2: 删除前检查用户是否有关联的活跃记录
            $activeTaskCount = \App\Models\Task::where('assigned_to', $user->id)
                ->whereIn('status', ['pending_confirmation', 'in_progress'])->count();
            $activeTicketCount = \App\Models\Ticket::where('assigned_to', $user->id)
                ->whereIn('status', ['open', 'in_progress'])->count();
            $ownedProjectCount = \App\Models\Project::where('created_by', $user->id)
                ->where('progress', '!=', 'completed')->count();

            if ($activeTaskCount > 0 || $activeTicketCount > 0 || $ownedProjectCount > 0) {
                session()->flash('error',
                    __('无法删除：该用户有 :activeTaskCount 个进行中任务、:activeTicketCount 个进行中工单、:ownedProjectCount 个未结项目。请先转移或完成后重试。', [
                        'activeTaskCount' => $activeTaskCount,
                        'activeTicketCount' => $activeTicketCount,
                        'ownedProjectCount' => $ownedProjectCount,
                    ])
                );
                $this->showDeleteModal = false;
                $this->deletingUserId = null;
                return;
            }

            $user->delete();
            session()->flash('success', __('用户已删除。'));
        }

        $this->showDeleteModal = false;
        $this->deletingUserId  = null;
    }

    public function render()
    {
        // [REVIEW-FIX-R4 #4 P3] 用户列表查询显式排除 password 字段：
        // User 模型 $hidden=['password','remember_token'] 会阻止 toArray()/JSON 序列化，
        // 但 Livewire 组件的 render() 将 paginator 传入 Blade，Blade 的 {{ }} 转义虽防 XSS，
        // 若前端有 {!! !!} 或 JSON 输出仍可能泄露。显式 select 最小字段集是纵深防御。
        $query = User::with('roles')
            ->select(['id', 'name', 'username', 'email', 'department', 'phone', 'source',
                      'ad_authenticated', 'ad_username', 'ad_display_name', 'ad_last_sync_at',
                      'is_active', 'last_login_at', 'created_at', 'updated_at'])
            ->when($this->search, fn($q) =>
                $q->where(fn($sub) =>
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('department', 'like', "%{$this->search}%")
                )
            )
            ->when($this->filterSource === 'local', fn($q) => $q->where('ad_authenticated', false))
            ->when($this->filterSource === 'ad',    fn($q) => $q->where('ad_authenticated', true))
            ->when($this->filterRole, fn($q) =>
                $q->whereHas('roles', fn($r) => $r->where('name', $this->filterRole))
            )
            ->orderBy('created_at', 'desc');

        return view('livewire.admin.user-manager', [
            'users' => $query->paginate(15),
            'roles' => Role::orderBy('name')->get(), 'categories' => ProjectCategory::where('is_active',true)->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => __('用户管理')]);
    }
}
