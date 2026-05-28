<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('ad_guid')->nullable()->after('username');
            $table->string('department')->nullable()->after('ad_guid');
            $table->string('position')->nullable()->after('department');
            $table->string('phone')->nullable()->after('position');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'ad_guid', 'department', 'position', 'phone', 'is_active', 'last_login_at']);
        });
    }
};
