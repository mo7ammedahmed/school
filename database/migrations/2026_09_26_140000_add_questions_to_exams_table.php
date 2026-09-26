<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exams previously had no place to keep their question paper; quizzes use a
     * `questions` JSON column, so mirror that here for imported Word documents.
     */
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->json('questions')->nullable()->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('questions');
        });
    }
};
