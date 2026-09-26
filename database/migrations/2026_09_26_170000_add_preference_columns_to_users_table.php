<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The locale/timezone/notification preferences the middleware and the
 * preferences screen read were never backed by columns, so saving them failed
 * and the interface language could not be remembered per user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('password');
            $table->string('timezone', 64)->nullable()->after('locale');
            $table->boolean('email_notifications')->default(true)->after('timezone');
            $table->boolean('push_notifications')->default(true)->after('email_notifications');
            $table->boolean('sms_notifications')->default(false)->after('push_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'locale',
                'timezone',
                'email_notifications',
                'push_notifications',
                'sms_notifications',
            ]);
        });
    }
};
