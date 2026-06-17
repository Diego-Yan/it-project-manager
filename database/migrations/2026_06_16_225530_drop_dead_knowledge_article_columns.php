<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // [REVIEW-FIX] SP3.4: 清理 knowledge_articles 表死字段
    // - tags JSON 列：已由 kb_article_tag 多对多关系替代
    // - summary / vector_ids / is_vectorized：向量化预留字段，项目未使用
    // - 这些字段仍在 DB 中存在但无代码引用，应清理以保持 schema 整洁
    public function up(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropColumn(['tags', 'summary', 'vector_ids', 'is_vectorized']);
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('category');
            $table->text('summary')->nullable()->after('content');
            $table->json('vector_ids')->nullable()->after('summary');
            $table->boolean('is_vectorized')->default(false)->after('vector_ids');
        });
    }
};
