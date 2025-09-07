<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SpecialtyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


// Routes publiques (sans authentification)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); // Seulement POST
    Route::post('login', [AuthController::class, 'login']);   
    Route::post('/login', [AuthController::class, 'login']);    // Seulement POST
});




// Routes publiques pour les spécialités
Route::get('specialties', [SpecialtyController::class, 'index']);
Route::get('specialties/{id}', [SpecialtyController::class, 'show']);
 // AVANT les routes protégées (middleware auth:sanctum), ajoutez :
 Route::get('/doctors/search', [App\Http\Controllers\Api\PatientController::class, 'searchDoctors']);


// Routes protégées (authentification requise)
Route::middleware('auth:sanctum')->group(function () {
    
    // Routes d'authentification
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Routes pour les PATIENTS
    Route::middleware('role:patient')->prefix('patient')->group(function () {
        Route::get('profile', [PatientController::class, 'profile']);
        Route::put('profile', [PatientController::class, 'updateProfile']);
        
        // Gestion des rendez-vous pour patients
        Route::get('appointments', [AppointmentController::class, 'patientAppointments']);
        Route::post('appointments', [AppointmentController::class, 'bookAppointment']);
        Route::get('appointments/{id}', [AppointmentController::class, 'showPatientAppointment']);
        Route::put('appointments/{id}/cancel', [AppointmentController::class, 'cancelAppointment']);
        
        // Recherche de médecins
        Route::get('doctors/search', [PatientController::class, 'searchDoctors']);
        Route::get('doctors/{id}/availability', [PatientController::class, 'getDoctorAvailability']);
       

        // Paiements
        Route::post('payments/simulate', [PaymentController::class, 'simulatePayment']);
        Route::get('payments/history', [PaymentController::class, 'patientPaymentHistory']);
        
        // Téléchargement de justificatifs
        Route::get('appointments/{id}/receipt', [AppointmentController::class, 'downloadReceipt']);
    });

    // Routes pour les MÉDECINS
    Route::middleware('role:doctor')->prefix('doctor')->group(function () {
        Route::get('profile', [DoctorController::class, 'profile']);
        Route::put('profile', [DoctorController::class, 'updateProfile']);
        Route::post('complete-profile', [DoctorController::class, 'completeProfile']);
        
        // Gestion des disponibilités
        Route::get('availabilities', [DoctorController::class, 'getAvailabilities']);
        Route::post('availabilities', [DoctorController::class, 'setAvailability']);
        Route::put('availabilities/{id}', [DoctorController::class, 'updateAvailability']);
        Route::delete('availabilities/{id}', [DoctorController::class, 'deleteAvailability']);
        
        // Gestion des rendez-vous pour médecins
        Route::get('appointments', [AppointmentController::class, 'doctorAppointments']);
        Route::get('appointments/{id}', [AppointmentController::class, 'showDoctorAppointment']);
        Route::put('appointments/{id}/confirm', [AppointmentController::class, 'confirmAppointment']);
        Route::put('appointments/{id}/reject', [AppointmentController::class, 'rejectAppointment']);
        Route::put('appointments/{id}/complete', [AppointmentController::class, 'completeAppointment']);
        Route::post('appointments/{id}/notes', [AppointmentController::class, 'addNotes']);
        
        // Statistiques médecin
        Route::get('statistics', [DoctorController::class, 'getStatistics']);
        Route::get('payments/summary', [DoctorController::class, 'getPaymentSummary']);
        
        // Téléchargement de justificatifs
        Route::get('appointments/{id}/receipt', [AppointmentController::class, 'downloadReceipt']);
    });

    // Routes pour les ADMINISTRATEURS
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        
        // Gestion des utilisateurs
        Route::get('users', [AdminController::class, 'getUsers']);
        Route::get('users/{id}', [AdminController::class, 'getUser']);
        Route::put('users/{id}/activate', [AdminController::class, 'activateUser']);
        Route::put('users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::delete('users/{id}', [AdminController::class, 'deleteUser']);
        
        // Gestion des médecins
        Route::get('doctors', [AdminController::class, 'getDoctors']);
        Route::put('doctors/{id}/verify', [AdminController::class, 'verifyDoctor']);
        Route::put('doctors/{id}/reject', [AdminController::class, 'rejectDoctor']);
        
        // Gestion des spécialités
        Route::post('specialties', [SpecialtyController::class, 'store']);
        Route::put('specialties/{id}', [SpecialtyController::class, 'update']);
        Route::delete('specialties/{id}', [SpecialtyController::class, 'destroy']);
        
        // Gestion des rendez-vous
        Route::get('appointments', [AdminController::class, 'getAllAppointments']);
        Route::get('appointments/statistics', [AdminController::class, 'getAppointmentStatistics']);
        
        // Gestion des paiements
        Route::get('payments', [AdminController::class, 'getAllPayments']);
        Route::get('payments/statistics', [AdminController::class, 'getPaymentStatistics']);
        Route::post('payments/{id}/refund', [AdminController::class, 'refundPayment']);
        
        // Rapports et statistiques
        Route::get('reports/monthly', [AdminController::class, 'getMonthlyReport']);
        Route::get('reports/specialty', [AdminController::class, 'getSpecialtyReport']);
        Route::get('statistics/overview', [AdminController::class, 'getOverviewStatistics']);
    });

    // Routes communes à tous les utilisateurs authentifiés
    Route::get('notifications', [AuthController::class, 'getNotifications']);
    Route::put('notifications/{id}/read', [AuthController::class, 'markNotificationAsRead']);
    Route::put('notifications/read-all', [AuthController::class, 'markAllNotificationsAsRead']);
    
    // Chat IA
    Route::post('ai/chat', [AuthController::class, 'aiChat']);
    Route::get('ai/conversations', [AuthController::class, 'getAiConversations']);
});

Route::get('/test-cors', function () {
    return response()->json([
        'message' => 'CORS fonctionne !',
        'timestamp' => now()
    ]);
});


// Ajouter cette route publique (avant les routes protégées)
Route::get('/specialties', function () {
    return response()->json(\App\Models\Specialty::all());
});


// Route de test pour vérifier l'API
Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Medical Appointment fonctionne correctement',
        'timestamp' => now(),
    ]);
});

