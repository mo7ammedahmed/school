<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'is_active']);
        });

        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admission_period_id')->nullable()->constrained('admission_periods')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('draft')->index();

            // Guardian info
            $table->string('guardian_first_name');
            $table->string('guardian_last_name');
            $table->string('guardian_email');
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_national_id')->nullable()->comment('Encrypted');

            // Student info
            $table->string('student_first_name');
            $table->string('student_last_name');
            $table->date('student_date_of_birth')->nullable();
            $table->string('student_gender')->nullable();
            $table->string('student_nationality')->nullable();
            $table->string('grade_applying')->nullable();
            $table->text('student_notes')->nullable();

            // Previous school
            $table->string('previous_school_name')->nullable();
            $table->string('previous_school_last_grade')->nullable();
            $table->string('previous_school_year_completed')->nullable();

            // Documents: JSON array of {name, path, type}
            $table->json('documents')->nullable();

            // Review/decision
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'admission_period_id']);
        });

        Schema::create('admission_application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_application_id')->constrained('admission_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['admission_application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_application_events');
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('admission_periods');
    }
};
