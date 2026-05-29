<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. 服务目录 ──────────────────────────────────
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('type')->default('custom'); // web|database|cache|queue|storage|api|custom
            $table->string('status')->default('healthy'); // healthy|degraded|down|maintenance
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('health_check_url')->nullable();
            $table->integer('health_check_interval')->default(300); // 秒
            $table->timestamp('last_health_check_at')->nullable();
            $table->json('tags')->nullable(); // 标签: ["production","k8s","beijing"]
            $table->timestamps();
        });

        // 服务依赖关系
        Schema::create('service_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('depends_on_id')->constrained('services')->onDelete('cascade');
            $table->string('type')->default('hard'); // hard|soft
            $table->unique(['service_id', 'depends_on_id']);
            $table->timestamps();
        });

        // ── 2. 变更管理 ──────────────────────────────────
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('config'); // release|config|rollback|hotfix
            $table->string('risk')->default('low'); // low|medium|high|critical
            $table->string('status')->default('draft'); // draft|pending_approval|approved|rejected|in_progress|completed|rolled_back
            $table->foreignId('requester_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('change_window_start')->nullable();
            $table->timestamp('change_window_end')->nullable();
            $table->text('rollback_plan')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->timestamps();
        });

        // ── 3. 发布管理 ──────────────────────────────────
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('change_request_id')->nullable()->constrained()->onDelete('set null');
            $table->string('version');
            $table->string('git_ref')->nullable();       // 如 main, v2.3.1, abc123
            $table->string('git_repo')->nullable();       // https://github.com/org/repo
            $table->text('changelog')->nullable();
            $table->string('status')->default('planned'); // planned|deploying|success|failed|rolled_back
            $table->foreignId('deployed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
        });

        // ── 4. 故障管理 ──────────────────────────────────
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('P3'); // P0|P1|P2|P3|P4
            $table->string('status')->default('open');  // open|investigating|mitigated|resolved|closed
            $table->foreignId('reported_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution')->nullable();
            $table->text('postmortem_url')->nullable();
            $table->timestamps();
        });

        // 故障处理时间线
        Schema::create('incident_timelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('action'); // created|assigned|investigating|mitigated|resolved|commented
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_timelines');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('releases');
        Schema::dropIfExists('change_requests');
        Schema::dropIfExists('service_dependencies');
        Schema::dropIfExists('services');
    }
};
