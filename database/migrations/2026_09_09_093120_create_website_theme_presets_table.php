<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_theme_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Preset name (e.g., "Modern Green", "Academic Blue")
            $table->string('slug')->unique(); // For reference
            $table->text('description')->nullable();
            $table->json('light_tokens'); // Light theme tokens
            $table->json('dark_tokens'); // Dark theme tokens
            $table->json('typography'); // Typography settings
            $table->json('radius'); // Radius settings
            $table->json('shadows'); // Shadow settings
            $table->json('buttons'); // Button settings
            $table->boolean('is_default')->default(false); // Whether this is the default preset
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['slug']);
            $table->index(['is_default']);
            $table->index(['enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_theme_presets');
    }
};
