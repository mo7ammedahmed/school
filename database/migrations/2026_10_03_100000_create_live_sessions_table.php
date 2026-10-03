<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A live lesson broadcast by a teacher.
 *
 * One row is one session: which offering (subject × section) it belongs to, who
 * opened it, the random path key MediaMTX serves it under, and — once the
 * teacher is done — the recording that MediaMTX wrote to disk and the material
 * row it was published as.
 *
 * `material_id` is a nullable pointer rather than a pivot: a session has at most
 * one recording material, and the pointer is filled by the finalize job, not by
 * the teacher. The FK to `materials` is created here; the reverse pointer
 * (`materials.source_live_session_id`) arrives in the next migration, so the two
 * tables reference each other without a circular create order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('stream_key', 64)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('viewer_peak')->default(0);
            $table->string('recording_status', 20)->default('pending');
            $table->string('recording_path')->nullable();
            $table->unsignedBigInteger('recording_size')->nullable();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['offering_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
