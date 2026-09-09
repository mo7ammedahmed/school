<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['academic_years', 'grade_levels', 'sections', 'rooms', 'semesters'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('name_ar')->nullable()->after('name');
                $table->string('name_en')->nullable()->after('name_ar');
            });

            // Copy existing names into both new fields.
            DB::table($table)->update([
                'name_ar' => DB::raw('name'),
                'name_en' => DB::raw('name'),
            ]);

            // The name column is now redundant — the model appends a
            // locale-aware `name` accessor instead.
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('name')->nullable();
            });

            // Recover the name from the new fields (prefer en, then ar).
            DB::table($table)->update([
                'name' => DB::raw('COALESCE(name_en, name_ar)'),
            ]);

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['name_ar', 'name_en']);
            });
        }
    }
};
