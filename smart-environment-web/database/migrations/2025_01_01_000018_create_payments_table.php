<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method');
            $table->string('payment_type')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('order_id')->unique()->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('snap_token')->nullable();
            $table->text('payment_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->string('va_number')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('midtrans_response')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
