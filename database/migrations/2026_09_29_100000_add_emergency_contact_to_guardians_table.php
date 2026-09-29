<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The guardian screens ask for an emergency contact and print it back.
 *
 * The create form, the edit form and the detail screen all carry
 * `emergency_contact`, and the controller validated it — but no column held it,
 * so the operator typed a phone number, the model dropped it on the floor, and
 * the detail screen showed "-" for a value that had just been entered. Adding
 * the column is what makes those three screens honest; removing the field would
 * lose the intent the UI has been expressing all along.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('guardians', 'emergency_contact')) {
            return;
        }

        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('emergency_contact', 20)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('guardians', 'emergency_contact')) {
            return;
        }

        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropColumn('emergency_contact');
        });
    }
};
