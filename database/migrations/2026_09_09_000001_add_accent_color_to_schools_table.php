<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('accent_color')->default('#bb8a3d')->after('secondary_color');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('accent_color');
        });
    }
};
