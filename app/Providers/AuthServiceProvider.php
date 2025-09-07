<?php

// app/Providers/AuthServiceProvider.php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Doctor;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gates pour les permissions
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-specialties', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('verify-doctors', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-payments', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-appointments', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('access-patient-features', function (User $user) {
            return $user->isPatient();
        });

        Gate::define('access-doctor-features', function (User $user) {
            return $user->isDoctor();
        });

        Gate::define('book-appointment', function (User $user) {
            return $user->isPatient();
        });

        Gate::define('manage-doctor-profile', function (User $user) {
            return $user->isDoctor();
        });

        Gate::define('view-appointment', function (User $user, Appointment $appointment) {
            return $user->isAdmin() || 
                   $appointment->patient_id === $user->id ||
                   ($user->doctor && $appointment->doctor_id === $user->doctor->id);
        });

        Gate::define('cancel-appointment', function (User $user, Appointment $appointment) {
            return $appointment->patient_id === $user->id && $appointment->canBeCancelled();
        });

        Gate::define('confirm-appointment', function (User $user, Appointment $appointment) {
            return $user->doctor && 
                   $appointment->doctor_id === $user->doctor->id && 
                   $appointment->canBeConfirmed();
        });

        // Configurer Sanctum
        Sanctum::ignoreMigrations();
    }
}

