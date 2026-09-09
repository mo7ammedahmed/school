<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->nullable();
            $table->json('title')->nullable(); // Multilingual: {"ar": "...", "en": "..."}
            $table->json('description')->nullable(); // Multilingual
            $table->string('template')->default('default')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('seo_metadata')->nullable(); // SEO title, description, OG image, etc.
            $table->json('settings')->nullable(); // Page-specific settings
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'slug']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pages');
    }
};
