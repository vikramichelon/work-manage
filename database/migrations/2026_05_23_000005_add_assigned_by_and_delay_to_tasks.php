<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Free-text name of whoever handed the task to the user
            // (boss, client, peer — may not be a system account).
            $table->string('assigned_by_name')->nullable()->after('assigned_to');

            // Optional explanation if actual hours overran estimated hours.
            $table->text('delay_reason')->nullable()->after('actual_hours');
        });

        // Seed a "Project" category alongside the existing Ads / SEO so the
        // user has all three "sides" listed by default.
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        if ($adminId) {
            DB::table('categories')->insertOrIgnore([
                'name' => 'Project',
                'color' => '#4F46E5',
                'description' => 'General project work',
                'created_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['assigned_by_name', 'delay_reason']);
        });
    }
};
