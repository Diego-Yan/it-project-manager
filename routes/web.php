<?php

use App\Http\Controllers\Auth\LoginController;
use App\Livewire\Dashboard;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectForm;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Admin\UserManager;
use App\Livewire\Admin\AdSettingsManager;
use App\Livewire\Admin\RoleManager;
use App\Livewire\Categories\CategoryManager;
use Illuminate\Support\Facades\Route;

// ── 机器人回调（无需登录，无需 CSRF，每分钟限 60 次） ──
Route::post('/api/bot/wechat', [\App\Http\Controllers\Api\BotController::class, 'wechat'])
    ->withoutMiddleware(['web', 'auth'])->middleware('throttle:60,1');
Route::post('/api/bot/dingtalk', [\App\Http\Controllers\Api\BotController::class, 'dingtalk'])
    ->withoutMiddleware(['web', 'auth'])->middleware('throttle:60,1');

// ── 认证路由 ──────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── 应用路由（需要登录） ───────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    // 个人视图
    Route::get('/my/projects', \App\Livewire\MyProjects::class)->name('my.projects');
    Route::get('/my/tasks', \App\Livewire\MyTasks::class)->name('my.tasks');
    Route::get('/my/tickets', \App\Livewire\MyTickets::class)->name('my.tickets');
    Route::get('/my/assets', \App\Livewire\MyAssets::class)->name('my.assets');

    // 项目管理
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', ProjectList::class)->name('index');
        Route::get('/create', ProjectForm::class)->name('create')->middleware('can:create projects');
        Route::get('/{project}', ProjectDetail::class)->name('show');
        Route::get('/{project}/kanban', \App\Livewire\Projects\TaskKanban::class)->name('kanban');
        Route::get('/{project}/edit', ProjectForm::class)->name('edit')->middleware('can:edit projects');
    });

    // 分类管理
    Route::get('/categories', CategoryManager::class)->name('categories.index')->middleware('can:view categories');

    // 地区管理
    Route::get('/admin/regions', \App\Livewire\Admin\RegionManager::class)->name('admin.regions')->middleware('can:view categories');

    // 用户管理
    Route::get('/admin/users', UserManager::class)->name('admin.users')->middleware('can:view users');

    // AD 域配置
    Route::get('/admin/ad-settings', AdSettingsManager::class)->name('admin.ad-settings')->middleware('can:view users');

    // ITSM (requires view projects permission)
    Route::prefix('itsm')->name('itsm.')->group(function () {
        Route::get('/tickets', \App\Livewire\Itsm\TicketBoard::class)->name('tickets')->middleware('can:view tickets');
        Route::get('/assets', \App\Livewire\Itsm\AssetManager::class)->name('assets')->middleware('can:view assets');
        Route::get('/knowledge', \App\Livewire\Itsm\KnowledgeBase::class)->name('knowledge')->middleware('can:view knowledge');
        Route::get('/services', \App\Livewire\Itsm\ServiceManager::class)->name('services')->middleware('can:manage assets');
        Route::get('/changes', \App\Livewire\Itsm\ChangeManager::class)->name('changes')->middleware('can:view changes');
        Route::get('/incidents', \App\Livewire\Itsm\IncidentManager::class)->name('incidents')->middleware('can:view incidents');
        Route::get('/zabbix', \App\Livewire\Itsm\ZabbixManager::class)->name('zabbix')->middleware('can:manage incidents');
        Route::get('/slas', \App\Livewire\Itsm\SlaManager::class)->name('slas')->middleware('can:manage slas');
    });

    // IM 接入配置
    Route::get('/admin/im', \App\Livewire\Admin\ImSettings::class)->name('admin.im')->middleware('can:manage roles');

    // Webhook 通知配置
    Route::get('/admin/webhooks', \App\Livewire\Admin\WebhookManager::class)->name('admin.webhooks')->middleware('can:manage roles');

    // 角色管理
    // AI 配置
    Route::get('/admin/ai', \App\Livewire\Admin\AiSettings::class)->name('admin.ai')->middleware('can:manage roles');

    // 角色管理
    Route::get('/admin/roles', RoleManager::class)->name('admin.roles')->middleware('can:manage roles');
});
