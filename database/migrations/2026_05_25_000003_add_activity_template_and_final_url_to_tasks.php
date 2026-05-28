<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('activity_template_id')->nullable()->after('website')
                ->constrained('activity_templates')->nullOnDelete();
            $table->string('final_url', 500)->nullable()->after('delay_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['activity_template_id']);
            $table->dropColumn(['activity_template_id', 'final_url']);
        });
    }
};
