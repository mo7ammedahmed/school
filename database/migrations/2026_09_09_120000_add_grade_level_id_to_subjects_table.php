<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('grade_level_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['school_id', 'grade_level_id']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['grade_level_id']);
            $table->dropIndex(['school_id', 'grade_level_id']);
            $table->dropColumn('grade_level_id');
        });
    }
};