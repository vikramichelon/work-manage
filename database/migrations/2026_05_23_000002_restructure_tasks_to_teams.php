<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rewire the tasks table from projects to teams.
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id', 'status']);
            $table->dropColumn('project_id');

            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['assigned_to']);
            $table->dropColumn('assigned_to');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->nullable()->after('team_id')
                ->constrained('team_members')->nullOnDelete();

            $table->index(['team_id', 'status']);
            $table->index('team_member_id');
        });

        // 2. Drop the now-unused project tables.
        Schema::dropIfExists('project_user');
        Schema::dropIfExists('projects');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Tasks restructure is not reversible — restore from backup if needed.'
        );
    }
};
