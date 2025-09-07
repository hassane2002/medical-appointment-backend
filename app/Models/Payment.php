<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_payment_intent_id',
        'status',
        'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
    ];

    // Constantes pour les statuts
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    // Constantes pour les passerelles
    const GATEWAY_STRIPE = 'stripe';
    const GATEWAY_CINETPAY = 'cinetpay';
    const GATEWAY_SIMULATOR = 'simulator';

    // Relations
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échec',
            self::STATUS_REFUNDED => 'Remboursé',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }

    public function getGatewayLabelAttribute()
    {
        $labels = [
            self::GATEWAY_STRIPE => 'Stripe',
            self::GATEWAY_CINETPAY => 'CinetPay',
            self::GATEWAY_SIMULATOR => 'Simulateur',
        ];

        return $labels[$this->payment_gateway] ?? 'Inconnu';
    }

    // Méthodes utilitaires
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canBeRefunded()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    // Méthodes d'actions
    public function markAsCompleted($transactionId = null, $response = null)
    {
        $updateData = ['status' => self::STATUS_COMPLETED];
        
        if ($transactionId) {
            $updateData['gateway_transaction_id'] = $transactionId;
        }
        
        if ($response) {
            $updateData['gateway_response'] = $response;
        }

        $this->update($updateData);

        // Mettre à jour le statut de paiement du rendez-vous
        $this->appointment->update(['payment_status' => Appointment::PAYMENT_STATUS_PAID]);
    }

    public function markAsFailed($response = null)
    {
        $updateData = ['status' => self::STATUS_FAILED];
        
        if ($response) {
            $updateData['gateway_response'] = $response;
        }

        $this->update($updateData);

        // Mettre à jour le statut de paiement du rendez-vous
        $this->appointment->update(['payment_status' => Appointment::PAYMENT_STATUS_FAILED]);
    }

    public function refund($response = null)
    {
        if (!$this->canBeRefunded()) {
            throw new \Exception('Ce paiement ne peut pas être remboursé');
        }

        $updateData = ['status' => self::STATUS_REFUNDED];
        
        if ($response) {
            $updateData['gateway_response'] = array_merge($this->gateway_response ?? [], $response);
        }

        $this->update($updateData);

        // Mettre à jour le statut de paiement du rendez-vous
        $this->appointment->update(['payment_status' => Appointment::PAYMENT_STATUS_REFUNDED]);
    }
}