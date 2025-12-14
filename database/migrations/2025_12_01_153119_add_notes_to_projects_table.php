<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if column already exists (from base migration)
        if (Schema::hasColumn('projects', 'notes')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'notes')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
