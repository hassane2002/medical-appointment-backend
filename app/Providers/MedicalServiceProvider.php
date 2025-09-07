<?php
// app/Providers/MedicalServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Services\AiChatService;
use App\Services\AppointmentService;
use App\Events\AppointmentBooked;
use App\Events\PaymentCompleted;
use App\Listeners\SendAppointmentNotification;
use App\Listeners\SendPaymentNotification;

class MedicalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Enregistrer les services principaux
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(PdfService::class, function ($app) {
            return new PdfService();
        });

        $this->app->singleton(AiChatService::class, function ($app) {
            return new AiChatService();
        });

        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService();
        });

        // Enregistrer les alias de services
        $this->app->alias(PaymentService::class, 'payment.service');
        $this->app->alias(NotificationService::class, 'notification.service');
        $this->app->alias(PdfService::class, 'pdf.service');
        $this->app->alias(AiChatService::class, 'ai.service');
        $this->app->alias(AppointmentService::class, 'appointment.service');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publier les fichiers de configuration
        $this->publishes([
            __DIR__.'/../../config/medical.php' => config_path('medical.php'),
        ], 'medical-config');

        // Publier les vues
        $this->publishes([
            __DIR__.'/../../resources/views/receipts' => resource_path('views/receipts'),
        ], 'medical-views');

        // Publier les migrations personnalisées si nécessaire
        $this->publishes([
            __DIR__.'/../../database/migrations/custom' => database_path('migrations'),
        ], 'medical-migrations');

        // Enregistrer les événements
        $this->registerEvents();

        // Enregistrer les Gates personnalisés
        $this->registerGates();

        // Enregistrer les commandes Artisan personnalisées
        $this->registerCommands();

        // Enregistrer les macros personnalisées
        $this->registerMacros();

        // Configuration des validations personnalisées
        $this->registerValidators();
    }

    /**
     * Enregistrer les événements et listeners
     */
    protected function registerEvents()
    {
        Event::listen(AppointmentBooked::class, SendAppointmentNotification::class);
        Event::listen(PaymentCompleted::class, SendPaymentNotification::class);

        // Événements du modèle Appointment
        Event::listen('eloquent.created: App\Models\Appointment', function ($appointment) {
            app(NotificationService::class)->sendAppointmentBookedNotification($appointment);
        });

        // Événements du modèle Payment
        Event::listen('eloquent.updated: App\Models\Payment', function ($payment) {
            if ($payment->isDirty('status') && $payment->status === 'completed') {
                app(NotificationService::class)->sendPaymentCompletedNotification($payment);
            }
        });

        // Événements du modèle Doctor
        Event::listen('eloquent.updated: App\Models\Doctor', function ($doctor) {
            if ($doctor->isDirty('is_verified') && $doctor->is_verified) {
                app(NotificationService::class)->sendDoctorVerifiedNotification($doctor);
            }
        });
    }

    /**
     * Enregistrer les Gates personnalisés
     */
    protected function registerGates()
    {
        // Gates pour les rendez-vous
        Gate::define('book-appointment', function ($user, $doctorId = null) {
            if (!$user->isPatient()) {
                return false;
            }

            // Vérifier les limites de réservation
            $dailyLimit = config('medical.limits.appointments.max_per_day_per_patient', 3);
            $todayAppointments = $user->patientAppointments()
                ->whereDate('appointment_date', today())
                ->count();

            return $todayAppointments < $dailyLimit;
        });

        Gate::define('manage-appointment', function ($user, $appointment) {
            return $user->isAdmin() || 
                   $appointment->patient_id === $user->id ||
                   ($user->doctor && $appointment->doctor_id === $user->doctor->id);
        });

        // Gates pour les paiements
        Gate::define('process-payment', function ($user, $appointment) {
            return $user->isPatient() && 
                   $appointment->patient_id === $user->id &&
                   $appointment->payment_method === 'online' &&
                   $appointment->payment_status === 'pending';
        });

        Gate::define('refund-payment', function ($user, $payment) {
            return $user->isAdmin() && $payment->canBeRefunded();
        });

        // Gates pour l'IA
        Gate::define('use-ai-chat', function ($user) {
            if (!config('medical.ai.enabled', true)) {
                return false;
            }

            // Vérifier les limites de taux
            $hourlyLimit = config('medical.ai.conversation.rate_limit', 50);
            $recentChats = \App\Models\AiConversation::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            return $recentChats < $hourlyLimit;
        });

        // Gates pour les médecins
        Gate::define('manage-doctor-availability', function ($user) {
            return $user->isDoctor() && $user->doctor && $user->doctor->is_verified;
        });

        Gate::define('complete-doctor-profile', function ($user) {
            return $user->isDoctor();
        });
    }

    /**
     * Enregistrer les commandes Artisan personnalisées
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\SendAppointmentReminders::class,
                \App\Console\Commands\CleanupOldData::class,
                \App\Console\Commands\GenerateSystemReport::class,
                \App\Console\Commands\ProcessFailedPayments::class,
                \App\Console\Commands\BackupDatabase::class,
            ]);
        }
    }

    /**
     * Enregistrer les macros personnalisées
     */
    protected function registerMacros()
    {
        // Macro pour la classe Request pour valider les rendez-vous
        \Illuminate\Http\Request::macro('validateAppointmentTime', function ($doctorId, $date, $time) {
            $doctor = \App\Models\Doctor::find($doctorId);
            if (!$doctor) {
                return false;
            }

            return $doctor->isAvailableAt($date, $time);
        });

        // Macro pour la classe Carbon pour les formats de date médicaux
        \Carbon\Carbon::macro('toMedicalDate', function () {
            return $this->format(config('medical.ui.date_format', 'd/m/Y'));
        });

        \Carbon\Carbon::macro('toMedicalTime', function () {
            return $this->format(config('medical.ui.time_format', 'H:i'));
        });

        \Carbon\Carbon::macro('toMedicalDateTime', function () {
            return $this->format(config('medical.ui.datetime_format', 'd/m/Y H:i'));
        });

        // Macro pour Collection pour formater les montants
        \Illuminate\Support\Collection::macro('formatCurrency', function ($currency = null) {
            $currency = $currency ?? config('medical.payments.default_currency', 'XOF');
            
            return $this->map(function ($amount) use ($currency) {
                return number_format($amount, 0, ',', ' ') . ' ' . $currency;
            });
        });
    }

    /**
     * Enregistrer les validateurs personnalisés
     */
    protected function registerValidators()
    {
        // Validateur pour les créneaux de rendez-vous
        \Illuminate\Support\Facades\Validator::extend('available_slot', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 3) {
                return false;
            }

            $doctorId = $parameters[0];
            $date = $parameters[1];
            $time = $value;

            $doctor = \App\Models\Doctor::find($doctorId);
            if (!$doctor || !$doctor->is_verified) {
                return false;
            }

            // Vérifier la disponibilité du médecin
            if (!$doctor->isAvailableAt($date, $time)) {
                return false;
            }

            // Vérifier qu'il n'y a pas déjà un rendez-vous
            return !\App\Models\Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();
        });

        // Validateur pour les numéros de téléphone sénégalais
        \Illuminate\Support\Facades\Validator::extend('senegal_phone', function ($attribute, $value, $parameters, $validator) {
            // Format accepté: 77 123 45 67 ou +221 77 123 45 67
            return preg_match('/^(\+221\s?)?([7][0-8])\s?(\d{3})\s?(\d{2})\s?(\d{2})$/', $value);
        });

        // Validateur pour les heures de travail
        \Illuminate\Support\Facades\Validator::extend('working_hours', function ($attribute, $value, $parameters, $validator) {
            $workingStart = config('medical.appointments.working_hours.start', '08:00');
            $workingEnd = config('medical.appointments.working_hours.end', '18:00');

            $time = \Carbon\Carbon::createFromFormat('H:i', $value);
            $start = \Carbon\Carbon::createFromFormat('H:i', $workingStart);
            $end = \Carbon\Carbon::createFromFormat('H:i', $workingEnd);

            return $time->between($start, $end);
        });

        // Messages d'erreur personnalisés
        \Illuminate\Support\Facades\Validator::replacer('available_slot', function ($message, $attribute, $rule, $parameters) {
            return 'Ce créneau n\'est pas disponible.';
        });

        \Illuminate\Support\Facades\Validator::replacer('senegal_phone', function ($message, $attribute, $rule, $parameters) {
            return 'Le numéro de téléphone doit être au format sénégalais (ex: 77 123 45 67).';
        });

        \Illuminate\Support\Facades\Validator::replacer('working_hours', function ($message, $attribute, $rule, $parameters) {
            $start = config('medical.appointments.working_hours.start', '08:00');
            $end = config('medical.appointments.working_hours.end', '18:00');
            return "L'heure doit être entre {$start} et {$end}.";
        });
    }

    /**
     * Méthodes utilitaires pour les services
     */
    public function provides()
    {
        return [
            PaymentService::class,
            NotificationService::class,
            PdfService::class,
            AiChatService::class,
            AppointmentService::class,
            'payment.service',
            'notification.service',
            'pdf.service',
            'ai.service',
            'appointment.service',
        ];
    }
}
