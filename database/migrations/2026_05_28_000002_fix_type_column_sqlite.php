<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite enforces the original CHECK(type IN ('new','improved')) constraint.
        // We need to drop and recreate the column without the constraint.
        if (DB::getDriverName() === 'sqlite') {
            // Step 1: add new column without constraint
            DB::statement('ALTER TABLE projects ADD COLUMN type_new VARCHAR NOT NULL DEFAULT "new"');
            // Step 2: copy existing data
            DB::statement('UPDATE projects SET type_new = type');
            // Step 3: drop old column
            DB::statement('ALTER TABLE projects DROP COLUMN type');
            // Step 4: rename new column
            DB::statement('ALTER TABLE projects RENAME COLUMN type_new TO type');
        }
        // MySQL: enum can be altered with ALTER TABLE ... MODIFY, but we'll skip
        // since existing MySQL rows already store 'new' or 'improved' as varchar.
    }

    public function down(): void
    {
        // No-op: reverting the column type constraint is not practical in SQLite
    }
};
