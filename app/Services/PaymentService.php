<?php

// app/Services/PaymentService.php

namespace App\Services;

use App\Models\Payment;
use App\Models\Appointment;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService
{
    public function __construct()
    {
        if (config('medical.payments.supported_gateways.stripe.enabled')) {
            Stripe::setApiKey(config('services.stripe.secret'));
        }
    }

    /**
     * Simuler un paiement
     */
    public function simulatePayment($appointmentId, $paymentMethod, $simulateSuccess = null)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        if ($simulateSuccess === null) {
            $successRate = config('medical.payments.simulator.success_rate', 90);
            $simulateSuccess = rand(1, 100) <= $successRate;
        }

        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'amount' => $appointment->payment_amount,
            'currency' => config('medical.payments.default_currency'),
            'payment_method' => $paymentMethod,
            'payment_gateway' => Payment::GATEWAY_SIMULATOR,
            'status' => Payment::STATUS_PENDING,
        ]);

        if ($simulateSuccess) {
            $transactionId = 'SIM_' . time() . '_' . rand(1000, 9999);
            $payment->markAsCompleted($transactionId, [
                'simulator_response' => 'success',
                'transaction_id' => $transactionId,
                'processed_at' => now(),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'transaction_id' => $transactionId,
            ];
        } else {
            $payment->markAsFailed([
                'simulator_response' => 'failed',
                'error' => 'Simulation d\'échec de paiement',
                'processed_at' => now(),
            ]);

            return [
                'success' => false,
                'payment' => $payment,
                'error' => 'Paiement refusé par la banque (simulation)',
            ];
        }
    }

    /**
     * Traiter un paiement Stripe
     */
    public function processStripePayment($appointmentId, $paymentMethodId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $paymentIntent = PaymentIntent::create([
            'amount' => $appointment->payment_amount * 100, // Centimes
            'currency' => strtolower(config('medical.payments.default_currency')),
            'payment_method' => $paymentMethodId,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'metadata' => [
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
            ]
        ]);

        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'amount' => $appointment->payment_amount,
            'currency' => config('medical.payments.default_currency'),
            'payment_method' => 'card',
            'payment_gateway' => Payment::GATEWAY_STRIPE,
            'gateway_payment_intent_id' => $paymentIntent->id,
            'gateway_response' => $paymentIntent->toArray(),
            'status' => Payment::STATUS_PENDING,
        ]);

        if ($paymentIntent->status === 'succeeded') {
            $payment->markAsCompleted($paymentIntent->id, [
                'stripe_payment_intent' => $paymentIntent->toArray()
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'payment_intent' => $paymentIntent,
            ];
        }

        return [
            'success' => false,
            'requires_action' => true,
            'payment' => $payment,
            'payment_intent' => $paymentIntent,
        ];
    }

    /**
     * Calculer les frais de paiement
     */
    public function calculateFees($amount, $gateway = 'stripe')
    {
        $feePercentage = config('medical.payments.fees.online_percentage', 2.5);
        $fees = ($amount * $feePercentage) / 100;
        
        return [
            'amount' => $amount,
            'fees' => $fees,
            'total' => $amount + $fees,
            'fee_percentage' => $feePercentage,
        ];
    }

    /**
     * Vérifier si un montant est dans les limites
     */
    public function validateAmount($amount)
    {
        $min = config('medical.payments.fees.minimum_amount', 5000);
        $max = config('medical.payments.fees.maximum_amount', 500000);

        return $amount >= $min && $amount <= $max;
    }
}
