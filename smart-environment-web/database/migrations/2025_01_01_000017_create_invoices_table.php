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
            $table->unsignedBigInteger('waste_delivery_id')->unique();
            $table->unsignedBigInteger('service_account_id');
            $table->string('invoice_number')->unique();
            $table->string('va_number')->unique()->nullable();
            $table->dateTime('va_expiry_at')->nullable();
            $table->string('snap_token')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_channel')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->enum('status', ['not_paid', 'paid', 'cancelled'])->default('not_paid');
            $table->dateTime('issued_at');
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('waste_delivery_id')->references('id')->on('waste_deliveries')->cascadeOnDelete();
            $table->foreign('service_account_id')->references('id')->on('service_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
