<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('reported_for')->nullable()->after('created_by')
                ->constrained('users')->onDelete('set null');
            $table->timestamp('user_confirmed_at')->nullable()->after('reported_for');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reported_for');
            $table->dropColumn('user_confirmed_at');
        });
    }
};
