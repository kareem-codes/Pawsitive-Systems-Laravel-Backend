<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying enum columns
        // The status column already exists with values stored as strings
        // No migration needed - we just need to ensure the application logic handles the new status
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed
    }
};
