<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every news screen carries a category; no column did.
 *
 * The create form makes it a required choice from a fixed list, the edit form
 * reads it back, and the admin list, the admin detail screen and both public
 * news screens print it. With no column behind it the model dropped the value on
 * save, an article could never be re-categorised, and the public site rendered a
 * blank where the category belongs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('news', 'category')) {
            return;
        }

        Schema::table('news', function (Blueprint $table): void {
            // The form offers general/academic/sports/events/announcements, so a
            // free string defaulting to `general` matches what the UI sends.
            $table->string('category', 50)->nullable()->default('general')->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('news', 'category')) {
            return;
        }

        Schema::table('news', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};
