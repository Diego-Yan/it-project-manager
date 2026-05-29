<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade'); // null = 全局
            $table->string('name');
            $table->string('url');
            $table->string('type')->default('custom'); // wechat | dingtalk | custom
            $table->json('events')->nullable(); // 哪些事件触发，null = 全部
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_configs');
    }
};
