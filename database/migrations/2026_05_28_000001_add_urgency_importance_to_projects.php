<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('urgency')->default('normal')->after('progress')->comment('紧急度: not_urgent/normal/urgent');
            $table->string('importance')->default('normal')->after('urgency')->comment('重要性: normal/important/very_important');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['urgency', 'importance']);
        });
    }
};
