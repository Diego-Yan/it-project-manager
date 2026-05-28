<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('message')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamps();
            $table->unique(['project_id', 'user_id']); // 同一用户对同一项目只能申请一次
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_applications');
    }
};
