<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('website_navigation_menus')->cascadeOnDelete();
            $table->string('label'); // Menu item text
            $table->json('label_translation')->nullable(); // Multilingual label
            $table->string('type')->default('page'); // page, url, route
            $table->foreignId('page_id')->nullable()->constrained('website_pages')->nullOnDelete();
            $table->string('url')->nullable(); // External URL
            $table->string('route')->nullable(); // Named route
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('icon')->nullable(); // Icon class
            $table->integer('order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->boolean('dropdown')->default(false); // Whether this item has a dropdown
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'menu_id', 'order']);
            $table->index(['school_id', 'enabled']);
            $table->index(['menu_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_navigation_items');
    }
};
