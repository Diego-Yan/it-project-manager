<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [REVIEW-FIX] H6: 为核心 ITSM 表添加软删除支持
     * Ticket/Task/Asset/Incident/ChangeRequest 原先硬删除导致数据不可恢复
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('assets', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('incidents', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('change_requests', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('tasks', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('assets', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('incidents', fn (Blueprint $t) => $t->dropSoftDeletes());
        Schema::table('change_requests', fn (Blueprint $t) => $t->dropSoftDeletes());
    }
};
