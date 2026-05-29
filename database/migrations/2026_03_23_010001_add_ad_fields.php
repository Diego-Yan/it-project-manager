<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration was a duplicate of 2026_03_20_010001_add_ad_auth_fields.
        // AD fields are already added by that migration.
        // This file kept as a placeholder to maintain migration history on production.
    }

    public function down(): void
    {
        // No-op: fields managed by 2026_03_20_010001_add_ad_auth_fields
    }
};
