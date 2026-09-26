<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // When the invoice was last delivered to the student's guardian.
            $table->timestamp('sent_at')->nullable()->after('status');
            // Which channels the last delivery used, e.g. {"email":true,"sms":false,"inapp":true}.
            $table->json('delivery_channels')->nullable()->after('sent_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('delivery_channels');
            $table->timestamp('paid_at')->nullable()->after('reminder_sent_at');

            $table->index(['school_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'sent_at']);
            $table->dropColumn(['sent_at', 'delivery_channels', 'reminder_sent_at', 'paid_at']);
        });
    }
};
