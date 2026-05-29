<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('projects')->onDelete('cascade');
            $table->string('link_type'); // blocks | relates_to | parent
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['project_id', 'target_id', 'link_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_links');
    }
};
