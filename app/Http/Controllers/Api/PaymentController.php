<?php
// app/Http/Controllers/Api/PaymentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    /**
     * Simuler un paiement
     */
    public function simulatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'payment_method' => 'required|string',
            'simulate_success' => 'boolean', // Pour tester les échecs
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $appointment = $user->patientAppointments()
                ->with(['doctor.user', 'doctor.specialty'])
                ->findOrFail($request->appointment_id);

            // Vérifier si le paiement existe déjà
            $existingPayment = Payment::where('appointment_id', $appointment->id)->first();
            
            if ($existingPayment && $existingPayment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous est déjà payé'
                ], 409);
            }

            // Simuler le paiement (90% de succès par défaut)
            $simulateSuccess = $request->get('simulate_success', rand(1, 10) <= 9);
            
            $paymentData = [
                'appointment_id' => $appointment->id,
                'patient_id' => $user->id,
                'amount' => $appointment->payment_amount,
                'currency' => 'XOF',
                'payment_method' => $request->payment_method,
                'payment_gateway' => Payment::GATEWAY_SIMULATOR,
            ];

            if ($existingPayment) {
                $payment = $existingPayment;
            } else {
                $payment = Payment::create($paymentData);
            }

            if ($simulateSuccess) {
                // Simuler un paiement réussi
                $transactionId = 'SIM_' . time() . '_' . rand(1000, 9999);
                $payment->markAsCompleted($transactionId, [
                    'simulator_response' => 'success',
                    'transaction_id' => $transactionId,
                    'processed_at' => now(),
                ]);

                // Notification de paiement réussi
                Notification::notifyPaymentCompleted($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement simulé avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId,
                        'amount' => $payment->formatted_amount,
                        'status' => 'completed',
                        'appointment_status' => $appointment->fresh()->status,
                    ]
                ]);
            } else {
                // Simuler un échec de paiement
                $payment->markAsFailed([
                    'simulator_response' => 'failed',
                    'error' => 'Simulation d\'échec de paiement',
                    'processed_at' => now(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Échec du paiement simulé',
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => 'failed',
                        'error' => 'Paiement refusé par la banque (simulation)',
                    ]
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement simulé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paiement réel avec Stripe
     */
    public function processStripePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'payment_method_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $user = $request->user();
            $appointment = $user->patientAppointments()
                ->findOrFail($request->appointment_id);

            // Vérifier si déjà payé
            $existingPayment = Payment::where('appointment_id', $appointment->id)
                ->where('status', Payment::STATUS_COMPLETED)
                ->first();
            
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous est déjà payé'
                ], 409);
            }

            // Créer le Payment Intent Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $appointment->payment_amount * 100, // Stripe utilise les centimes
                'currency' => 'xof', // Franc CFA
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $user->id,
                    'doctor_name' => $appointment->doctor->user->full_name,
                ]
            ]);

            // Créer l'enregistrement de paiement
            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $user->id,
                'amount' => $appointment->payment_amount,
                'currency' => 'XOF',
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

                Notification::notifyPaymentCompleted($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement traité avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'transaction_id' => $paymentIntent->id,
                        'amount' => $payment->formatted_amount,
                        'status' => 'completed',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Paiement nécessite une action supplémentaire',
                'data' => [
                    'requires_action' => true,
                    'payment_intent' => [
                        'id' => $paymentIntent->id,
                        'client_secret' => $paymentIntent->client_secret,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du paiement Stripe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmer un paiement Stripe
     */
    public function confirmStripePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
            
            $payment = Payment::where('gateway_payment_intent_id', $paymentIntent->id)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé'
                ], 404);
            }

            if ($paymentIntent->status === 'succeeded') {
                $payment->markAsCompleted($paymentIntent->id, [
                    'stripe_payment_intent' => $paymentIntent->toArray()
                ]);

                Notification::notifyPaymentCompleted($payment);

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement confirmé avec succès',
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => 'completed',
                    ]
                ]);
            } else {
                $payment->markAsFailed([
                    'stripe_payment_intent' => $paymentIntent->toArray()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Paiement échoué',
                    'error' => 'Le paiement n\'a pas pu être confirmé'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Historique des paiements du patient
     */
    public function patientPaymentHistory(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = $user->payments()->with(['appointment.doctor.user', 'appointment.doctor.specialty']);

            // Filtrer par statut
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filtrer par période
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                    ],
                    'summary' => [
                        'total_spent' => $user->payments()->completed()->sum('amount'),
                        'pending_payments' => $user->payments()->where('status', Payment::STATUS_PENDING)->count(),
                        'completed_payments' => $user->payments()->completed()->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un paiement
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $payment = $user->payments()
                ->with(['appointment.doctor.user', 'appointment.doctor.specialty'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'formatted_amount' => $payment->formatted_amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'status_label' => $payment->status_label,
                    'payment_method' => $payment->payment_method,
                    'payment_gateway' => $payment->payment_gateway,
                    'gateway_label' => $payment->gateway_label,
                    'gateway_transaction_id' => $payment->gateway_transaction_id,
                    'created_at' => $payment->created_at,
                    'appointment' => [
                        'id' => $payment->appointment->id,
                        'reference_number' => $payment->appointment->reference_number,
                        'date' => $payment->appointment->appointment_date,
                        'time' => $payment->appointment->appointment_time->format('H:i'),
                        'doctor_name' => $payment->appointment->doctor->user->full_name,
                        'specialty' => $payment->appointment->doctor->specialty->name,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Demander un remboursement (Patient)
     */
    public function requestRefund(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $payment = $user->payments()->findOrFail($id);

            if (!$payment->canBeRefunded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce paiement ne peut pas être remboursé'
                ], 409);
            }

            // Créer une notification pour l'admin
            Notification::createForUser(
                1, // ID admin (à adapter selon votre système)
                'Demande de remboursement',
                "Demande de remboursement pour le paiement #{$payment->id}. Raison: {$request->reason}",
                Notification::TYPE_INFO,
                $payment->appointment_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande de remboursement envoyée. Un administrateur va traiter votre demande.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la demande de remboursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des paiements (pour le dashboard)
     */
    public function getPaymentStatistics(Request $request)
    {
        try {
            $user = $request->user();
            
            $stats = [
                'total_payments' => $user->payments()->count(),
                'completed_payments' => $user->payments()->completed()->count(),
                'pending_payments' => $user->payments()->where('status', Payment::STATUS_PENDING)->count(),
                'failed_payments' => $user->payments()->where('status', Payment::STATUS_FAILED)->count(),
                'total_amount_paid' => $user->payments()->completed()->sum('amount'),
                'this_month_payments' => $user->payments()->thisMonth()->completed()->sum('amount'),
                'average_payment' => $user->payments()->completed()->avg('amount'),
                'payment_methods' => $user->payments()->completed()
                    ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('payment_method')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}