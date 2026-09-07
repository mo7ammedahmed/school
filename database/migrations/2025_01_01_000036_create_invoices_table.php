<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->decimal('amount_paid', 19, 4)->default(0);
            $table->decimal('balance_due', 19, 4)->default(0);
            $table->string('currency')->default('SAR');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('vat_amount')->nullable()->comment('ZATCA VAT amount');
            $table->string('vat_rate')->nullable()->comment('ZATCA VAT rate');
            $table->text('qr_code_data')->nullable()->comment('ZATCA QR data');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
