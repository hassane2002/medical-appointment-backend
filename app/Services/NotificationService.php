<?php
// app/Services/NotificationService.php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Envoyer une notification de rendez-vous réservé
     */
    public function sendAppointmentBookedNotification(Appointment $appointment)
    {
        // Notification pour le patient
        $this->createNotification(
            $appointment->patient_id,
            'Rendez-vous demandé',
            "Votre demande de rendez-vous avec Dr. {$appointment->doctor->user->full_name} le {$appointment->full_date_time} a été enregistrée.",
            Notification::TYPE_SUCCESS,
            $appointment->id
        );

        // Notification pour le médecin
        $this->createNotification(
            $appointment->doctor->user_id,
            'Nouveau rendez-vous',
            "Vous avez une nouvelle demande de rendez-vous de {$appointment->patient->full_name} pour le {$appointment->full_date_time}.",
            Notification::TYPE_INFO,
            $appointment->id
        );

        // Envoyer email si activé
        if (config('medical.notifications.email.enabled')) {
            $this->sendAppointmentBookedEmail($appointment);
        }
    }

    /**
     * Envoyer une notification de paiement complété
     */
    public function sendPaymentCompletedNotification(Payment $payment)
    {
        $this->createNotification(
            $payment->patient_id,
            'Paiement confirmé',
            "Votre paiement de {$payment->formatted_amount} a été traité avec succès.",
            Notification::TYPE_SUCCESS,
            $payment->appointment_id
        );

        if (config('medical.notifications.email.enabled')) {
            $this->sendPaymentCompletedEmail($payment);
        }
    }

    /**
     * Envoyer une notification de médecin vérifié
     */
    public function sendDoctorVerifiedNotification($doctor)
    {
        $this->createNotification(
            $doctor->user_id,
            'Compte vérifié',
            'Félicitations ! Votre compte médecin a été vérifié. Vous pouvez maintenant recevoir des rendez-vous.',
            Notification::TYPE_SUCCESS
        );
    }

    /**
     * Créer une notification en base
     */
    protected function createNotification($userId, $title, $message, $type, $appointmentId = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_appointment_id' => $appointmentId,
        ]);
    }

    /**
     * Envoyer des emails (à implémenter selon vos besoins)
     */
    protected function sendAppointmentBookedEmail(Appointment $appointment)
    {
        // Mail::to($appointment->patient->email)->send(new AppointmentBookedMail($appointment));
        // Mail::to($appointment->doctor->user->email)->send(new NewAppointmentMail($appointment));
    }

    protected function sendPaymentCompletedEmail(Payment $payment)
    {
        // Mail::to($payment->patient->email)->send(new PaymentCompletedMail($payment));
    }
}