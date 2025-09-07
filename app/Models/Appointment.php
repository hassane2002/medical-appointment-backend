<?php
// app/Models/Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'reason',
        'status',
        'payment_method',
        'payment_status',
        'payment_amount',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'payment_amount' => 'decimal:2',
    ];

    // Constantes pour les statuts
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    const PAYMENT_METHOD_ONLINE = 'online';
    const PAYMENT_METHOD_CABINET = 'cabinet';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_REFUNDED = 'refunded';
    const PAYMENT_STATUS_FAILED = 'failed';

    // Event pour générer automatiquement le numéro de référence
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (empty($appointment->reference_number)) {
                $appointment->reference_number = 'RDV' . date('Ymd') . Str::padLeft(rand(1, 9999), 4, '0');
            }
        });
    }

    // Relations
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function receipt()
    {
        return $this->hasOne(AppointmentReceipt::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_appointment_id');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED])
                    ->orderBy('appointment_date')
                    ->orderBy('appointment_time');
    }

    public function scopeToday($query)
    {
        return $query->where('appointment_date', now()->toDateString());
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    // Accessors
    public function getFullDateTimeAttribute()
    {
        return $this->appointment_date->format('d/m/Y') . ' à ' . $this->appointment_time->format('H:i');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_NO_SHOW => 'Absent',
        ];

        return $labels[$this->status] ?? 'Inconnu';
    }

    public function getPaymentStatusLabelAttribute()
    {
        $labels = [
            self::PAYMENT_STATUS_PENDING => 'En attente',
            self::PAYMENT_STATUS_PAID => 'Payé',
            self::PAYMENT_STATUS_REFUNDED => 'Remboursé',
            self::PAYMENT_STATUS_FAILED => 'Échec',
        ];

        return $labels[$this->payment_status] ?? 'Inconnu';
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->payment_amount, 0, ',', ' ') . ' FCFA';
    }

    // Méthodes utilitaires
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]) 
               && $this->appointment_date >= now()->toDateString();
    }

    public function canBeConfirmed()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCompleted()
    {
        return $this->status === self::STATUS_CONFIRMED 
               && $this->appointment_date <= now()->toDateString();
    }

    public function requiresPayment()
    {
        return $this->payment_method === self::PAYMENT_METHOD_ONLINE;
    }

    public function isPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    // Méthodes d'actions
    public function confirm()
    {
        if ($this->requiresPayment() && !$this->isPaid()) {
            throw new \Exception('Le paiement doit être effectué avant confirmation');
        }

        $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    public function cancel()
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Ce rendez-vous ne peut pas être annulé');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);

        // Si payé en ligne, marquer pour remboursement
        if ($this->requiresPayment() && $this->isPaid()) {
            $this->update(['payment_status' => self::PAYMENT_STATUS_REFUNDED]);
        }
    }

    public function complete()
    {
        if (!$this->canBeCompleted()) {
            throw new \Exception('Ce rendez-vous ne peut pas être marqué comme terminé');
        }

        $this->update(['status' => self::STATUS_COMPLETED]);
    }
}