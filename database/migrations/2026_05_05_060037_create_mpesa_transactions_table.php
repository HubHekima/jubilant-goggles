<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('set null');
    
            // M-Pesa Specific IDs
            $table->string('merchant_request_id')->index();
            $table->string('checkout_request_id')->unique();
            
            // Result Data
            $table->integer('result_code'); // 0 for Success
            $table->string('result_desc');
            $table->string('mpesa_receipt')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('phone_number')->nullable();
            
            // Raw Payload for debugging
            $table->json('callback_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
