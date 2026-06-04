<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 工单关联系统分类（project_categories）
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('region_id')
                ->constrained('project_categories')->onDelete('set null');
        });

        // IT 工程师负责的系统（用户 ↔ 项目分类）
        Schema::create('user_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('project_categories')->onDelete('cascade');
            $table->unique(['user_id', 'category_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('user_categories');
    }
};
