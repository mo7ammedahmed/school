<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('revisionable_type'); // The model being revised (e.g., App\Models\WebsitePage)
            $table->foreignId('revisionable_id'); // The ID of the model being revised
            $table->json('data'); // Snapshot of the model's data
            $table->string('changed_by')->nullable(); // User who made the change
            $table->string('description')->nullable(); // Description of changes
            $table->timestamps();

            $table->index(['school_id', 'revisionable_type', 'revisionable_id'], 'website_revisions_school_idx');
            $table->index(['school_id', 'created_at'], 'website_revisions_school_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_revisions');
    }
};
