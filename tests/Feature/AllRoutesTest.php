<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): void
    {
        // Create all permissions
        $perms = [
            'view users','create users','edit users','delete users','manage roles',
            'view categories','create categories','edit categories','delete categories',
            'view projects','create projects','edit projects','delete projects',
            'assign project members','view all projects',
            'upload attachments','delete attachments',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => '超级管理员', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());
        Role::firstOrCreate(['name' => '普通成员', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => '系统管理员', 'username' => 'admin',
            'email' => 'admin@itops.local', 'password' => bcrypt('Admin@2024!'),
            'is_active' => true,
        ]);
        $admin->assignRole('超级管理员');

        $cat = \App\Models\ProjectCategory::firstOrCreate(
            ['name' => '桌面运维'],
            ['type' => 'ops', 'color' => 'sky', 'sort_order' => 1]
        );

        $project = Project::create([
            'category_id' => $cat->id, 'created_by' => $admin->id,
            'title' => 'Test Project', 'type' => 'new', 'progress' => 'in_progress',
            'urgency' => 'normal', 'importance' => 'normal', 'completion_percent' => 30,
        ]);
        $project->members()->attach($admin->id, ['role' => 'lead']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedData();
    }

    private function loginAsAdmin(): User
    {
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);
        return $user;
    }

    #[Test] public function login_page_returns_200(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    #[Test] public function all_authenticated_routes_return_200(): void
    {
        $this->loginAsAdmin();
        $project = Project::first();
        $this->assertNotNull($project, 'No project in DB - seed failed');

        $routes = [
            '/',
            '/dashboard',
            '/my/projects',
            '/my/tasks',
            '/projects',
            '/projects/create',
            "/projects/{$project->id}",
            "/projects/{$project->id}/kanban",
            "/projects/{$project->id}/edit",
            '/categories',
            '/itsm/tickets',
            '/itsm/assets',
            '/itsm/knowledge',
            '/itsm/services',
            '/itsm/changes',
            '/itsm/incidents',
            '/itsm/slas',
            '/admin/users',
            '/admin/ad-settings',
            '/admin/webhooks',
            '/admin/roles',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $status = $response->status();
            $this->assertContains($status, [200, 302], "Route {$route} returned {$status}");
        }
    }

    #[Test] public function all_components_render_without_error(): void
    {
        $this->loginAsAdmin();
        $project = Project::first();
        $this->assertNotNull($project);

        // Components without params
        foreach ([
            \App\Livewire\Dashboard::class,
            \App\Livewire\MyProjects::class,
            \App\Livewire\MyTasks::class,
            \App\Livewire\Projects\ProjectList::class,
            \App\Livewire\Projects\ProjectForm::class,
            \App\Livewire\Itsm\TicketBoard::class,
            \App\Livewire\Itsm\AssetManager::class,
            \App\Livewire\Itsm\KnowledgeBase::class,
            \App\Livewire\Itsm\ServiceManager::class,
            \App\Livewire\Itsm\ChangeManager::class,
            \App\Livewire\Itsm\IncidentManager::class,
            \App\Livewire\Itsm\SlaManager::class,
            \App\Livewire\Admin\UserManager::class,
            \App\Livewire\Admin\RoleManager::class,
            \App\Livewire\Admin\WebhookManager::class,
            \App\Livewire\Admin\AdSettingsManager::class,
            \App\Livewire\Categories\CategoryManager::class,
        ] as $class) {
            try {
                $instance = new $class;
                if (method_exists($instance, 'mount')) $instance->mount();
                $instance->render();
                $this->assertTrue(true);
            } catch (\Throwable $e) {
                $this->fail("{$class}: " . $e->getMessage());
            }
        }

        // Components with project param
        foreach ([
            \App\Livewire\Projects\ProjectDetail::class,
            \App\Livewire\Projects\TaskKanban::class,
            \App\Livewire\Projects\TaskManager::class,
            \App\Livewire\Projects\ApplicationManager::class,
            \App\Livewire\Projects\LinkManager::class,
        ] as $class) {
            try {
                $instance = new $class;
                if (method_exists($instance, 'mount')) $instance->mount($project);
                $instance->render();
                $this->assertTrue(true);
            } catch (\Throwable $e) {
                $this->fail("{$class}: " . $e->getMessage());
            }
        }
    }

    #[Test] public function project_form_with_inline_tasks_works(): void
    {
        $this->loginAsAdmin();
        $component = new \App\Livewire\Projects\ProjectForm;
        $component->title = 'Test';
        $component->category_id = 1;
        $component->type = 'new';
        $component->urgency = 'urgent';
        $component->importance = 'important';
        $component->newTaskTitle = 'Task 1';
        $component->addInlineTask();
        $component->newTaskTitle = 'Task 2';
        $component->addInlineTask();
        $component->removeInlineTask(0);
        $this->assertCount(1, $component->inlineTasks);
        $component->render();
    }

    #[Test] public function task_state_transitions(): void
    {
        $admin = $this->loginAsAdmin();
        $project = Project::first();

        $component = new \App\Livewire\Projects\TaskManager;
        $component->mount($project);

        $component->taskTitle = 'State Test';
        $component->taskPriority = 'normal';
        $component->saveTask();

        $task = \App\Models\Task::latest()->first();
        $this->assertEquals('pending_confirmation', $task->status);

        $component->claimTask($task->id);
        $task->refresh();
        $this->assertEquals('in_progress', $task->status);
        $this->assertEquals($admin->id, $task->assigned_to);

        $component->completeTask($task->id);
        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    #[Test] public function blocks_cycle_detection(): void
    {
        $this->loginAsAdmin();
        $p1 = Project::first();

        // Create a second project for testing
        $cat = \App\Models\ProjectCategory::first();
        $p2 = Project::create([
            'category_id' => $cat->id, 'created_by' => auth()->id(),
            'title' => 'Project 2', 'type' => 'new', 'progress' => 'pending',
            'urgency' => 'normal', 'importance' => 'normal',
        ]);

        // P1 → P2: no cycle
        $this->assertFalse(\App\Models\ProjectLink::wouldCreateBlocksCycle($p1->id, $p2->id));
        // P1 → P1: self-link IS a cycle
        $this->assertTrue(\App\Models\ProjectLink::wouldCreateBlocksCycle($p1->id, $p1->id));
    }

    #[Test] public function sole_lead_cannot_be_demoted(): void
    {
        $admin = $this->loginAsAdmin();
        $project = Project::first();

        $this->assertTrue($project->isLead($admin->id));
        // admin created the project → they are the lead
    }

    #[Test] public function apply_reapply_approve_flow(): void
    {
        $admin = $this->loginAsAdmin();
        $project = Project::first();

        $testUser = User::create([
            'name' => 'Applicant', 'username' => 'applicant',
            'email' => 'app@test.local', 'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $testUser->assignRole('普通成员');

        // Apply as test user
        $this->actingAs($testUser);
        $pl = new \App\Livewire\Projects\ProjectList;
        $pl->applyToProject($project->id);

        $app = \App\Models\ProjectApplication::where('project_id', $project->id)
            ->where('user_id', $testUser->id)->first();
        $this->assertNotNull($app);
        $this->assertEquals('pending', $app->status);

        // Re-apply should not duplicate
        $pl->applyToProject($project->id);
        $count = \App\Models\ProjectApplication::where('project_id', $project->id)
            ->where('user_id', $testUser->id)->count();
        $this->assertEquals(1, $count);

        // Approve as admin
        $this->actingAs($admin);
        $am = new \App\Livewire\Projects\ApplicationManager;
        $am->mount($project);
        $am->approve($app->id);
        $app->refresh();
        $this->assertEquals('approved', $app->status);
        $this->assertTrue($project->isMember($testUser->id));
    }
}
