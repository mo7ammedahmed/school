<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->json('header')->nullable(); // Header settings
            $table->json('footer')->nullable(); // Footer settings
            $table->json('announcement_bar')->nullable(); // Announcement bar settings
            $table->json('contact')->nullable(); // Contact information
            $table->json('social')->nullable(); // Social media links
            $table->json('seo')->nullable(); // Global SEO settings
            $table->json('theme_toggle')->nullable(); // Theme toggle settings
            $table->timestamps();

            $table->unique(['school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
