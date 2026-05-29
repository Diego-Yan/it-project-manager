<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 知识库附件（PDF/Word/PPT/图片等）
        Schema::create('kb_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->string('file_name');          // 原始文件名
            $table->string('file_path');          // 存储路径
            $table->string('mime_type');          // MIME 类型
            $table->unsignedBigInteger('file_size'); // 文件大小（字节）
            $table->string('preview_type')->default('download'); // download|image|pdf|office|video
            $table->timestamps();
        });

        // 知识库文章标签（独立表，支持更多标签）
        Schema::create('kb_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color')->default('zinc');
            $table->integer('count')->default(0); // 使用计数
            $table->timestamps();
        });

        // 文章-标签多对多
        Schema::create('kb_article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('kb_tags')->onDelete('cascade');
            $table->unique(['article_id', 'tag_id']);
        });

        // 向量化准备：添加 embedding 相关字段
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('content');     // AI 摘要
            $table->json('vector_ids')->nullable()->after('summary');  // 向量存储 ID（预留）
            $table->boolean('is_vectorized')->default(false)->after('vector_ids');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_article_tag');
        Schema::dropIfExists('kb_tags');
        Schema::dropIfExists('kb_attachments');
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropColumn(['summary', 'vector_ids', 'is_vectorized']);
        });
    }
};
