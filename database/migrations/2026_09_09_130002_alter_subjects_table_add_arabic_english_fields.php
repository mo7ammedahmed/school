<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_ar');
        });

        // Copy existing name to both new fields
        DB::table('subjects')->update([
            'name_ar' => DB::raw('name'),
            'name_en' => DB::raw('name'),
        ]);

        // Now we can remove the old name column
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('name')->nullable();
        });

        // Recover the name from the new fields (prefer en, then ar)
        DB::table('subjects')->update([
            'name' => DB::raw('COALESCE(name_en, name_ar)'),
        ]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};
