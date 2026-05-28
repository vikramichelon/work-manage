<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Preserve the existing team labels as categories so the user keeps
        //    their Ads / SEO setup. (Task rows themselves are wiped because
        //    their assignee model is changing fundamentally.)
        if (Schema::hasTable('teams')) {
            DB::statement(<<<'SQL'
                INSERT INTO categories (name, color, description, created_by, created_at, updated_at)
                SELECT name, color, description, created_by, created_at, updated_at
                FROM teams
SQL);
        }

        DB::table('tasks')->delete();

        // 2. Drop old team relations from tasks.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropIndex(['team_id', 'status']);
            $table->dropColumn('team_id');

            $table->dropForeign(['team_member_id']);
            $table->dropIndex(['team_member_id']);
            $table->dropColumn('team_member_id');
        });

        // 3. Wire tasks to categories + assignee (login user).
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('category_id')->after('id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->after('category_id')
                ->constrained('users')->nullOnDelete();

            $table->index(['category_id', 'status']);
            $table->index('assigned_to');
        });

        // 4. Drop the unused team tables.
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Tasks restructure (teams→categories) is not reversible — restore from backup.'
        );
    }
};
