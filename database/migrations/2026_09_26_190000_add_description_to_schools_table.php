<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('description_ar')->nullable()->after('name_en');
            $table->string('description_en')->nullable()->after('description_ar');
        });

        // Copy empty strings to both new fields (or null, depending on preference)
        DB::table('schools')->update([
            'description_ar' => '',
            'description_en' => '',
        ]);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['description_ar', 'description_en']);
        });
    }
};
