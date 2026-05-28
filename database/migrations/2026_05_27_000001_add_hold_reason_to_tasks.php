<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Required whenever status = 'hold'; explains why the task is paused.
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('delay_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('hold_reason');
        });
    }
};
