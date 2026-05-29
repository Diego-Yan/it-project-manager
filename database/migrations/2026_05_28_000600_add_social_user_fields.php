<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('source')->default('local')->after('username'); // local|ad|wechat|dingtalk
            $table->string('wechat_userid')->nullable()->after('ad_username');
            $table->string('dingtalk_userid')->nullable()->after('wechat_userid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['source', 'wechat_userid', 'dingtalk_userid']);
        });
    }
};
