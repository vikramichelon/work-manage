<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('estimated_minutes')->default(30);
            $table->foreignId('category_id')->nullable()
                ->constrained()->nullOnDelete(); // suggested team
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed defaults — derived from the user's tracking sheet (Jan–Mar).
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $catMap  = DB::table('categories')->pluck('id', 'name')->toArray(); // name => id

        $seeds = [
            // Landing pages
            ['LP create',                       150, 'Project'],
            ['LP changes',                       45, 'Project'],
            ['LP replica with changes',          90, 'Project'],
            ['LP live + CRM link',               30, 'Ads'],
            // Website / page
            ['Page create',                      50, 'Project'],
            ['Page design (heavy)',             180, 'Project'],
            ['Section add',                      45, 'Project'],
            ['Website audit',                    90, 'SEO'],
            ['Website changes',                  45, 'Project'],
            ['Website convert (html ↔ php)',    180, 'Project'],
            ['Website development (new)',        90, 'Project'],
            // SEO technical
            ['Sitemap update',                   15, 'SEO'],
            ['Robots.txt update',                15, 'SEO'],
            ['Meta / interlink update',          30, 'SEO'],
            ['URL redirection',                  20, 'SEO'],
            // Tracking & analytics
            ['GTM add / update',                 15, 'Ads'],
            ['Pixel code add / update',          20, 'Ads'],
            ['GA / GSC verification',            15, 'Ads'],
            // UI elements
            ['CTA add / change',                 25, 'Project'],
            ['Popup add / remove',               25, 'Project'],
            ['Logo / Banner / Favicon change',   15, 'Project'],
            ['Image add / change / remove',      20, 'Project'],
            ['Image convert to WebP (bulk)',     90, 'Project'],
            ['Floating / WhatsApp icon add',     20, 'Project'],
            // Content
            ['Blog post add',                    25, 'SEO'],
            ['Blog bulk import + featured img', 120, 'SEO'],
            ['FAQ add',                          20, 'SEO'],
            ['Media / press section add',        40, 'Project'],
            ['Doctor profile add / remove',      30, 'Project'],
            // Forms & leads
            ['Form create (phpmailer / wp)',     35, 'Project'],
            ['Form testing',                     20, 'Ads'],
            ['Form submit error resolve',        30, 'Project'],
            // Recurring daily
            ['Ads speed work',                   60, 'Ads'],
            ['SEO speed work',                   75, 'SEO'],
            // Investigation
            ['Issue debug / fix',                60, 'Project'],
            ['Responsive / mobile issue resolve',35, 'Project'],
            ['404 page create',                  25, 'Project'],
        ];

        $now = now();
        foreach ($seeds as [$name, $minutes, $catName]) {
            DB::table('activity_templates')->insertOrIgnore([
                'name'              => $name,
                'estimated_minutes' => $minutes,
                'category_id'       => $catMap[$catName] ?? null,
                'created_by'        => $adminId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_templates');
    }
};
