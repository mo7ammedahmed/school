<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interface_translations', function (Blueprint $table) {
            $table->id();
            $table->char('source_hash', 64)->unique();
            $table->string('english', 300);
            $table->text('arabic');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps(6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interface_translations');
    }
};
