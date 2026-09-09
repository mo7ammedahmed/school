<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->string('mime_type');
            $table->bigInteger('size')->unsigned(); // File size in bytes
            $table->string('type')->default('image'); // image, video, document
            $table->json('dimensions')->nullable(); // For images/videos: {"width": 800, "height": 600}
            $table->string('alt_text')->nullable(); // Accessibility text
            $table->string('caption')->nullable(); // Caption
            $table->json('metadata')->nullable(); // EXIF, etc.
            $table->timestamps();

            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_media');
    }
};
