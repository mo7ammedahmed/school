<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_navigation_items', function (Blueprint $table) {
            $table->string('label_ar')->nullable()->after('label');
            $table->string('label_en')->nullable()->after('label_ar');
        });

        // Copy data from label_translation to the new fields
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $arExpr = "JSON_UNQUOTE(JSON_EXTRACT(label_translation, '$.ar'))";
            $enExpr = "JSON_UNQUOTE(JSON_EXTRACT(label_translation, '$.en'))";
        } elseif ($driver === 'sqlite') {
            $arExpr = "label_translation ->> '$.ar'";
            $enExpr = "label_translation ->> '$.en'";
        } else {
            throw new Exception("Unsupported database driver: {$driver}");
        }

        DB::table('website_navigation_items')->update([
            'label_ar' => DB::raw($arExpr),
            'label_en' => DB::raw($enExpr),
        ]);

        // Remove the old columns
        Schema::table('website_navigation_items', function (Blueprint $table) {
            $table->dropColumn(['label', 'label_translation']);
        });
    }

    public function down(): void
    {
        Schema::table('website_navigation_items', function (Blueprint $table) {
            $table->string('label')->nullable();
            $table->json('label_translation')->nullable();
        });

        // Recover the label_translation from the new fields
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::table('website_navigation_items')->update([
                'label_translation' => DB::raw("JSON_OBJECT('ar', label_ar, 'en', label_en)"),
            ]);
        } elseif ($driver === 'sqlite') {
            // In SQLite, we can use json_object function (available in SQLite 3.38.0+)
            DB::table('website_navigation_items')->update([
                'label_translation' => DB::raw("json_object('ar', label_ar, 'en', label_en)"),
            ]);
        } else {
            throw new Exception("Unsupported database driver: {$driver}");
        }

        Schema::table('website_navigation_items', function (Blueprint $table) {
            $table->dropColumn(['label_ar', 'label_en']);
        });
    }
};
