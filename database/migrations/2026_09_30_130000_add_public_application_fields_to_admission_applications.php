<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public application form asks for a guardian's occupation and address, the
 * student's address, the previous school's address and why the family is
 * leaving. None of it had a column, so all five answers were collected,
 * validated and dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->string('guardian_occupation')->nullable();
            $table->text('guardian_address')->nullable();
            $table->text('student_address')->nullable();
            $table->text('previous_school_address')->nullable();
            $table->text('reason_for_leaving')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropColumn([
                'guardian_occupation',
                'guardian_address',
                'student_address',
                'previous_school_address',
                'reason_for_leaving',
            ]);
        });
    }
};
