<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── CI/CD 流水线定义 ────────────────────────────
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('git_repo')->nullable();
            $table->string('trigger')->default('push'); // push|tag|manual|schedule
            $table->json('stages_config')->nullable();   // 阶段配置
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 流水线运行记录
        Schema::create('pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->onDelete('cascade');
            $table->string('commit_sha', 40)->nullable();
            $table->string('branch')->nullable();
            $table->string('status')->default('pending'); // pending|running|success|failed|cancelled
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('logs_url')->nullable();
            $table->timestamps();
        });

        // 阶段执行记录
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_run_id')->constrained()->onDelete('cascade');
            $table->string('name');          // build|test|deploy-dev|deploy-staging|deploy-prod
            $table->integer('order')->default(0);
            $table->string('status')->default('pending'); // pending|running|success|failed|skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('logs_url')->nullable();
        });

        // ── 环境管理 ────────────────────────────────────
        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');          // dev|staging|prod
            $table->string('type')->default('server'); // server|k8s|serverless
            $table->string('host_url')->nullable();
            $table->json('config_hash')->nullable();   // 配置指纹
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        // 部署记录
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained()->onDelete('cascade');
            $table->foreignId('release_id')->nullable()->constrained()->onDelete('set null');
            $table->string('version');
            $table->string('status')->default('deploying'); // deploying|success|failed|rolled_back
            $table->foreignId('deployed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('deployed_at')->nullable();
            $table->text('logs_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
        Schema::dropIfExists('environments');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipeline_runs');
        Schema::dropIfExists('pipelines');
    }
};
