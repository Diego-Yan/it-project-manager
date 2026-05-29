<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Use raw SQL for SQLite to avoid table-recreate issues with foreign keys
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE projects ADD COLUMN region_id INTEGER REFERENCES regions(id) ON DELETE SET NULL');
            DB::statement('ALTER TABLE tickets ADD COLUMN region_id INTEGER REFERENCES regions(id) ON DELETE SET NULL');
        } else {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('category_id')->constrained()->onDelete('set null');
            });
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('project_id')->constrained()->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
        Schema::dropIfExists('regions');
    }
};
