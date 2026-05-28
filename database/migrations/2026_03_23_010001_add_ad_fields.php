<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // AD 域认证相关字段
            $table->string('ad_domain')->nullable()->after('email')->comment('AD 域名');
            $table->string('ad_username')->nullable()->after('ad_domain')->comment('AD 用户名');
            $table->string('ad_display_name')->nullable()->after('ad_username')->comment('AD 显示名称');
            $table->string('ad_email')->nullable()->after('ad_display_name')->comment('AD 邮箱');
            $table->boolean('ad_authenticated')->default(false)->after('ad_email')->comment('是否为 AD 认证用户');
            $table->timestamp('ad_last_sync_at')->nullable()->after('ad_authenticated')->comment('AD 最后同步时间');

            // 索引
            $table->index(['ad_domain', 'ad_username'], 'idx_ad_user');
        });

        Schema::table('users', function (Blueprint $table) {
            // 允许 email 为空（AD 用户可能有 email 字段）
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_ad_user');
            $table->dropColumn([
                'ad_domain',
                'ad_username',
                'ad_display_name',
                'ad_email',
                'ad_authenticated',
                'ad_last_sync_at'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
