<?php
// app/Console/Commands/SendAppointmentReminders.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Envoyer les rappels de rendez-vous';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $this->info('Envoi des rappels de rendez-vous...');

        // Rappels 24h à l'avance
        if (config('medical.appointments.reminder_times.24_hours', true)) {
            $this->send24HourReminders();
        }

        // Rappels 2h à l'avance
        if (config('medical.appointments.reminder_times.2_hours', true)) {
            $this->send2HourReminders();
        }

        $this->info('Rappels envoyés avec succès !');
    }

    private function send24HourReminders()
    {
        $tomorrow = Carbon::tomorrow();
        
        $appointments = Appointment::with(['patient', 'doctor.user'])
            ->where('appointment_date', $tomorrow->toDateString())
            ->where('status', 'confirmed')
            ->get();

        foreach ($appointments as $appointment) {
            $this->notificationService->createNotification(
                $appointment->patient_id,
                'Rappel de rendez-vous',
                "N'oubliez pas votre rendez-vous demain à {$appointment->appointment_time->format('H:i')} avec Dr. {$appointment->doctor->user->full_name}.",
                'info',
                $appointment->id
            );
        }

        $this->line("Rappels 24h envoyés : {$appointments->count()}");
    }

    private function send2HourReminders()
    {
        $in2Hours = Carbon::now()->addHours(2);
        
        $appointments = Appointment::with(['patient', 'doctor.user'])
            ->where('appointment_date', $in2Hours->toDateString())
            ->whereTime('appointment_time', '>=', $in2Hours->subMinutes(15)->format('H:i'))
            ->whereTime('appointment_time', '<=', $in2Hours->addMinutes(15)->format('H:i'))
            ->where('status', 'confirmed')
            ->get();

        foreach ($appointments as $appointment) {
            $this->notificationService->createNotification(
                $appointment->patient_id,
                'Rappel imminent',
                "Votre rendez-vous avec Dr. {$appointment->doctor->user->full_name} est dans 2 heures ({$appointment->appointment_time->format('H:i')}).",
                'warning',
                $appointment->id
            );
        }

        $this->line("Rappels 2h envoyés : {$appointments->count()}");
    }
}