<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix for SQLite Enum check constraint failure for 'partial' status
        // We recreate the table structure with 'status' as a simple string instead of enum

        DB::transaction(function () {
            // 1. Create new table without Enum check constraint
            // Note: We match the current schema including 'paid_at'

            DB::statement('PRAGMA foreign_keys=OFF;');

            DB::statement("
                CREATE TABLE IF NOT EXISTS payments_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    sale_id INTEGER,
                    due_date DATE,
                    amount INTEGER DEFAULT 0 NOT NULL,
                    status VARCHAR(255) DEFAULT 'unpaid' NOT NULL,
                    note VARCHAR(255),
                    created_at DATETIME,
                    updated_at DATETIME,
                    paid_at DATETIME
                );
            ");

            // 2. Adjust columns if Schema builder was used differently (ensure compatibility)
            // Just raw copy is safer if column names match

            DB::statement("
                INSERT INTO payments_temp (id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at)
                SELECT id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at 
                FROM payments;
            ");

            // 3. Swap tables
            DB::statement("DROP TABLE payments;");
            DB::statement("ALTER TABLE payments_temp RENAME TO payments;");

            DB::statement('PRAGMA foreign_keys=ON;');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed, string status is compatible with enum values
    }
};
