<?php

// [REVIEW-FIX] R8.1~R8.4: 为高频查询列补充缺失索引
// - tickets.status: SidebarComposer + TicketBoard 按状态过滤/计数
// - assets.status: MyAssets 按状态过滤
// - assets.assigned_to: SidebarComposer + MyAssets 按负责人过滤
// - project_logs.created_at: Dashboard 按时间排序
// - incidents.status: SidebarComposer + IncidentManager 按状态统计

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tickets: 按 status 过滤的查询非常频繁
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status');
        });

        // assets: status 和 assigned_to 都是高频过滤条件
        Schema::table('assets', function (Blueprint $table) {
            $table->index('status');
            $table->index('assigned_to');
        });

        // project_logs: Dashboard 每页加载都按 created_at 排序
        Schema::table('project_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        // incidents: SidebarComposer 每次都 count whereIn status
        Schema::table('incidents', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to']);
        });
        Schema::table('project_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
