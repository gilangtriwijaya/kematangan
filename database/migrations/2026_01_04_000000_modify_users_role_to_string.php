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
        // Use raw statement to avoid requiring doctrine/dbal for this change
        DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(191) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the original enum for rollback. Adjust values if they change in future.
        DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('superadmin','admin','verifikator','opd') NOT NULL");
    }
};
