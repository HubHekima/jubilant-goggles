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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // For the QR code
            $table->foreignId('event_id')->constrained();
            $table->foreignId('ticket_type_id')->constrained();
            
            // Buyer Info
            $table->string('buyer_name');
            $table->string('buyer_email')->index();
            $table->string('buyer_phone');
            
            // Status & Payment
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('mpesa_reference')->nullable()->unique();
            
            // Validation
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
