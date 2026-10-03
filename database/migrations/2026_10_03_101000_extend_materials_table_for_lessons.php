<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materials learn to carry video.
 *
 * A material was a downloadable document: title, path, type, size. A lesson
 * video — uploaded by a teacher or recorded from a live session — is the same
 * row with three extra facts: which kind of media it is, how long it runs, and
 * (for a recording) which live session produced it.
 *
 * `kind` is a plain string rather than an enum column: SQLite and MySQL disagree
 * about enums, and Decision 4 keeps migrations portable between them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('kind', 20)->default('file')->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('source_live_session_id')->nullable()->constrained('live_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_live_session_id');
            $table->dropColumn(['kind', 'duration_seconds', 'thumbnail_path']);
        });
    }
};
