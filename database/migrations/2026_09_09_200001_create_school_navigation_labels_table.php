<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_navigation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_navigation_labels');
    }
};
