<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three columns the admissions review screen has always used and no migration
 * ever created.
 *
 * `AdmissionReviewService` filters the review queue on `priority` and
 * `assigned_to`, writes both when an application is assigned or re-prioritised,
 * and appends to `internal_notes` when a reviewer adds a note. Reading a column
 * that does not exist is silent (the attribute is just null), so the queue
 * looked empty and the buttons appeared to work; writing one is not, which is
 * what the "Add Internal Note" button did.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->string('priority')->default('medium')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('internal_notes');
        });
    }
};
