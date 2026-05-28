<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'first_draft' is 11 chars — won't fit in varchar(10).
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status', 20)->default('todo')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status', 10)->default('todo')->change();
        });
    }
};
