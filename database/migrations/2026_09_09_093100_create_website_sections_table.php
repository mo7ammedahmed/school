<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('website_pages')->cascadeOnDelete();
            $table->string('type'); // hero, about, statistics, etc.
            $table->string('name')->nullable(); // Display name for the section
            $table->integer('order')->default(0);
            $table->json('content')->nullable(); // Structured content
            $table->json('settings')->nullable(); // Section settings
            $table->json('styling')->nullable(); // Section styling
            $table->boolean('enabled')->default(true);
            $table->json('visibility')->nullable(); // Visibility rules (desktop/mobile/all)
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'page_id', 'order']);
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sections');
    }
};
