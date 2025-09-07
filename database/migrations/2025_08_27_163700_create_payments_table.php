<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF'); // Franc CFA
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_gateway', 50)->nullable(); // stripe, cinetpay, simulateur
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_payment_intent_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('gateway_response')->nullable(); // Réponse de l'API de paiement
            $table->timestamps();

            // Clés étrangères
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            
            // Index pour optimiser les requêtes
            $table->index(['appointment_id', 'status']);
            $table->index(['patient_id', 'created_at']);
            $table->index('gateway_transaction_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
