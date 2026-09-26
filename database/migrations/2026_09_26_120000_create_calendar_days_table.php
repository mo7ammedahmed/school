<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Author-editable calendar days. Everything that can be derived from
     * existing records (exams, assessment due dates, term boundaries, events)
     * is aggregated by the academic calendar read model instead of duplicated
     * here — this table holds only what nothing else knows about.
     */
    public function up(): void
    {
        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->date('end_date')->nullable();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_instructional')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'date']);
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
    }
};
