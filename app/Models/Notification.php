<?php

// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'related_appointment_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Constantes pour les types
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'related_appointment_id');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_INFO => 'Information',
            self::TYPE_SUCCESS => 'Succès',
            self::TYPE_WARNING => 'Attention',
            self::TYPE_ERROR => 'Erreur',
        ];

        return $labels[$this->type] ?? 'Inconnu';
    }

    public function getTypeColorAttribute()
    {
        $colors = [
            self::TYPE_INFO => '#2196F3',
            self::TYPE_SUCCESS => '#4CAF50',
            self::TYPE_WARNING => '#FF9800',
            self::TYPE_ERROR => '#F44336',
        ];

        return $colors[$this->type] ?? '#757575';
    }

    // Méthodes utilitaires
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    // Méthodes statiques pour créer des notifications
    public static function createForUser($userId, $title, $message, $type = self::TYPE_INFO, $appointmentId = null)
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_appointment_id' => $appointmentId,
        ]);
    }

    public static function notifyAppointmentBooked($appointment)
    {
        // Notification pour le patient
        self::createForUser(
            $appointment->patient_id,
            'Rendez-vous demandé',
            "Votre demande de rendez-vous avec Dr. {$appointment->doctor->user->full_name} le {$appointment->full_date_time} a été enregistrée.",
            self::TYPE_SUCCESS,
            $appointment->id
        );

        // Notification pour le médecin
        self::createForUser(
            $appointment->doctor->user_id,
            'Nouveau rendez-vous',
            "Vous avez une nouvelle demande de rendez-vous de {$appointment->patient->full_name} pour le {$appointment->full_date_time}.",
            self::TYPE_INFO,
            $appointment->id
        );
    }

    public static function notifyAppointmentConfirmed($appointment)
    {
        self::createForUser(
            $appointment->patient_id,
            'Rendez-vous confirmé',
            "Votre rendez-vous avec Dr. {$appointment->doctor->user->full_name} le {$appointment->full_date_time} a été confirmé.",
            self::TYPE_SUCCESS,
            $appointment->id
        );
    }

    public static function notifyPaymentCompleted($payment)
    {
        self::createForUser(
            $payment->patient_id,
            'Paiement confirmé',
            "Votre paiement de {$payment->formatted_amount} a été traité avec succès.",
            self::TYPE_SUCCESS,
            $payment->appointment_id
        );
    }
}

