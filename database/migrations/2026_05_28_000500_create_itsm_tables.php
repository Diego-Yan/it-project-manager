<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. 工单系统 ────────────────────────────────
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('request'); // incident|request|change|problem
            $table->string('priority')->default('medium'); // low|medium|high|critical
            $table->string('status')->default('open'); // open|in_progress|resolved|closed
            $table->string('source')->default('portal'); // phone|email|portal|walk_in
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 工单评论/处理记录
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->integer('time_spent')->default(0); // 处理耗时（分钟）
            $table->boolean('is_internal')->default(false); // 内部备注 vs 公开回复
            $table->timestamps();
        });

        // ── 2. 资产管理 ────────────────────────────────
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();          // 资产编号: IT-2024-0001
            $table->string('name');
            $table->string('type')->default('other');       // laptop|desktop|printer|switch|server|monitor|software|license|other
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('status')->default('in_use');    // in_use|available|repair|retired
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('location')->nullable();         // 位置：3楼A区
            $table->string('department')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ── 3. 知识库 ──────────────────────────────────
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category')->default('general'); // network|hardware|software|account|printer|other
            $table->json('tags')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });

        // ── 4. SLA 配置 ────────────────────────────────
        Schema::create('slas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('priority')->unique(); // low|medium|high|critical
            $table->integer('response_minutes');  // 响应时限
            $table->integer('resolution_minutes'); // 解决时限
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('slas');
    }
};
