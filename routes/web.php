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

    // 用户管理
    Route::get('/admin/users', UserManager::class)->name('admin.users')->middleware('can:view users');

    // AD 域配置
    Route::get('/admin/ad-settings', AdSettingsManager::class)->name('admin.ad-settings')->middleware('can:view users');

    // DevOps
    Route::prefix('devops')->name('devops.')->group(function () {
        Route::get('/', \App\Livewire\DevOps\Dashboard::class)->name('dashboard');
        Route::get('/services', \App\Livewire\DevOps\ServiceManager::class)->name('services');
        Route::get('/changes', \App\Livewire\DevOps\ChangeManager::class)->name('changes');
        Route::get('/releases', \App\Livewire\DevOps\ReleaseManager::class)->name('releases');
        Route::get('/incidents', \App\Livewire\DevOps\IncidentManager::class)->name('incidents');
    });

    // Webhook 通知配置
    Route::get('/admin/webhooks', \App\Livewire\Admin\WebhookManager::class)->name('admin.webhooks')->middleware('can:manage roles');

    // 角色管理
    Route::get('/admin/roles', RoleManager::class)->name('admin.roles')->middleware('can:manage roles');
});
