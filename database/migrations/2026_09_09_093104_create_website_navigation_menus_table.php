<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'slug']);
            $table->index(['school_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_navigation_menus');
    }
};
