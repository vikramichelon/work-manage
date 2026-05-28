<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores the moment the task entered WIP — used by the auto
        // time-tracker to fold elapsed working minutes into actual_hours
        // when the task leaves WIP.
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('wip_started_at')->nullable()->after('actual_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('wip_started_at');
        });
    }
};
