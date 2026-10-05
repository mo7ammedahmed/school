<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->text('content_ar')->nullable();
            $table->boolean('show_in_navigation')->default(false);
            $table->unsignedSmallInteger('navigation_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropColumn(['content_ar', 'show_in_navigation', 'navigation_order']);
        });
    }
};
