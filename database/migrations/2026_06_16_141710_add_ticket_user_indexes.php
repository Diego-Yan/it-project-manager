<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [REVIEW-FIX] I3: 为高频查询字段添加数据库索引
     * - tickets.assigned_to: SidebarComposer/MyTickets/AiChat 按处理人查询
     * - tickets.created_by: MyTickets 按创建者查询
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('assigned_to');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['created_by']);
        });
    }
};
