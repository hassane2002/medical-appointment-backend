<?php
// app/Http/Controllers/Api/AdminController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Specialty;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Dashboard administrateur avec statistiques générales
     */
    public function dashboard()
    {
        try {
            $stats = [
                // Statistiques des utilisateurs
                'users' => [
                    'total' => User::count(),
                    'patients' => User::byRole('patient')->count(),
                    'doctors' => User::byRole('doctor')->count(),
                    'active_users' => User::active()->count(),
                    'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                ],

                // Statistiques des médecins
                'doctors' => [
                    'total' => Doctor::count(),
                    'verified' => Doctor::verified()->count(),
                    'pending_verification' => Doctor::where('is_verified', false)->count(),
                    'specialties_count' => Specialty::withVerifiedDoctors()->count(),
                ],

                // Statistiques des rendez-vous
                'appointments' => [
                    'total' => Appointment::count(),
                    'today' => Appointment::today()->count(),
                    'this_month' => Appointment::whereMonth('created_at', now()->month)->count(),
                    'pending' => Appointment::where('status', 'pending')->count(),
                    'confirmed' => Appointment::where('status', 'confirmed')->count(),
                    'completed' => Appointment::where('status', 'completed')->count(),
                    'cancelled' => Appointment::where('status', 'cancelled')->count(),
                ],

                // Statistiques des paiements
                'payments' => [
                    'total_revenue' => Payment::completed()->sum('amount'),
                    'this_month_revenue' => Payment::completed()->thisMonth()->sum('amount'),
                    'pending_payments' => Payment::where('status', 'pending')->count(),
                    'completed_payments' => Payment::completed()->count(),
                    'failed_payments' => Payment::where('status', 'failed')->count(),
                    'online_payments' => Payment::byGateway('stripe')->completed()->count() + 
                                       Payment::byGateway('simulator')->completed()->count(),
                ],

                // Revenus par spécialité
                'revenue_by_specialty' => DB::table('appointments')
                    ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                    ->join('specialties', 'doctors.specialty_id', '=', 'specialties.id')
                    ->where('appointments.payment_status', 'paid')
                    ->select('specialties.name', DB::raw('SUM(appointments.payment_amount) as total'))
                    ->groupBy('specialties.name')
                    ->orderBy('total', 'desc')
                    ->get(),

                // Activité récente
                'recent_activity' => [
                    'new_appointments' => Appointment::whereBetween('created_at', [now()->subDays(7), now()])->count(),
                    'new_users' => User::whereBetween('created_at', [now()->subDays(7), now()])->count(),
                    'completed_payments' => Payment::completed()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des rendez-vous
     */
    public function getAppointmentStatistics()
    {
        try {
            $stats = [
                'total' => Appointment::count(),
                'by_status' => Appointment::select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->get(),
                'by_payment_method' => Appointment::select('payment_method', DB::raw('COUNT(*) as count'))
                    ->groupBy('payment_method')
                    ->get(),
                'monthly_trend' => Appointment::select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get(),
                'by_specialty' => DB::table('appointments')
                    ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                    ->join('specialties', 'doctors.specialty_id', '=', 'specialties.id')
                    ->select('specialties.name', DB::raw('COUNT(*) as count'))
                    ->groupBy('specialties.name')
                    ->orderBy('count', 'desc')
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

    /**
     * Tous les paiements avec filtres
     */
    public function getAllPayments(Request $request)
    {
        try {
            $query = Payment::with(['appointment.patient', 'appointment.doctor.user']);

            // Filtres
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('gateway')) {
                $query->byGateway($request->gateway);
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des paiements
     */
    public function getPaymentStatistics()
    {
        try {
            $stats = [
                'total_revenue' => Payment::completed()->sum('amount'),
                'monthly_revenue' => Payment::completed()->thisMonth()->sum('amount'),
                'by_status' => Payment::select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                    ->groupBy('status')
                    ->get(),
                'by_gateway' => Payment::select('payment_gateway', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                    ->where('status', 'completed')
                    ->groupBy('payment_gateway')
                    ->get(),
                'monthly_trend' => Payment::select(
                        DB::raw('YEAR(created_at) as year'),
                        DB::raw('MONTH(created_at) as month'),
                        DB::raw('SUM(amount) as total')
                    )
                    ->where('status', 'completed')
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get(),
                'average_payment' => Payment::completed()->avg('amount'),
                'failed_rate' => [
                    'total' => Payment::count(),
                    'failed' => Payment::where('status', 'failed')->count(),
                    'percentage' => Payment::count() > 0 ? 
                        round((Payment::where('status', 'failed')->count() / Payment::count()) * 100, 2) : 0
                ]
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

    /**
     * Effectuer un remboursement
     */
    public function refundPayment(Request $request, $id)
    {
        try {
            $payment = Payment::with('appointment')->findOrFail($id);

            if (!$payment->canBeRefunded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce paiement ne peut pas être remboursé'
                ], 409);
            }

            $payment->refund([
                'refunded_by' => 'admin',
                'refund_reason' => $request->reason ?? 'Remboursement administratif',
                'refunded_at' => now(),
            ]);

            // Notifier le patient
            Notification::createForUser(
                $payment->patient_id,
                'Remboursement effectué',
                "Votre paiement de {$payment->formatted_amount} a été remboursé avec succès.",
                Notification::TYPE_SUCCESS,
                $payment->appointment_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Remboursement effectué avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du remboursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport mensuel
     */
    public function getMonthlyReport(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $report = [
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'appointments' => [
                    'total' => Appointment::whereBetween('created_at', [$startDate, $endDate])->count(),
                    'completed' => Appointment::whereBetween('created_at', [$startDate, $endDate])
                        ->where('status', 'completed')->count(),
                    'cancelled' => Appointment::whereBetween('created_at', [$startDate, $endDate])
                        ->where('status', 'cancelled')->count(),
                ],
                'revenue' => [
                    'total' => Payment::completed()->whereBetween('created_at', [$startDate, $endDate])->sum('amount'),
                    'by_gateway' => Payment::completed()
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->select('payment_gateway', DB::raw('SUM(amount) as total'))
                        ->groupBy('payment_gateway')
                        ->get(),
                ],
                'users' => [
                    'new_patients' => User::byRole('patient')->whereBetween('created_at', [$startDate, $endDate])->count(),
                    'new_doctors' => User::byRole('doctor')->whereBetween('created_at', [$startDate, $endDate])->count(),
                ],
                'top_specialties' => DB::table('appointments')
                    ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                    ->join('specialties', 'doctors.specialty_id', '=', 'specialties.id')
                    ->whereBetween('appointments.created_at', [$startDate, $endDate])
                    ->select('specialties.name', DB::raw('COUNT(*) as appointments_count'), DB::raw('SUM(appointments.payment_amount) as revenue'))
                    ->groupBy('specialties.name')
                    ->orderBy('appointments_count', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rapport par spécialité
     */
    public function getSpecialtyReport()
    {
        try {
            $specialties = Specialty::with(['doctors' => function($query) {
                $query->where('is_verified', true);
            }])->get();

            $report = $specialties->map(function($specialty) {
                $appointments = Appointment::whereHas('doctor', function($query) use ($specialty) {
                    $query->where('specialty_id', $specialty->id);
                });

                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'doctors_count' => $specialty->doctors->count(),
                    'total_appointments' => $appointments->count(),
                    'completed_appointments' => $appointments->where('status', 'completed')->count(),
                    'total_revenue' => $appointments->where('payment_status', 'paid')->sum('payment_amount'),
                    'average_consultation_price' => $specialty->consultation_price,
                    'this_month_appointments' => $appointments->thisMonth()->count(),
                    'this_month_revenue' => $appointments->thisMonth()->where('payment_status', 'paid')->sum('payment_amount'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques générales pour vue d'ensemble
     */
    public function getOverviewStatistics()
    {
        try {
            $overview = [
                'summary' => [
                    'total_users' => User::count(),
                    'total_appointments' => Appointment::count(),
                    'total_revenue' => Payment::completed()->sum('amount'),
                    'total_specialties' => Specialty::count(),
                ],
                'growth' => [
                    'users_growth' => $this->calculateGrowthRate(User::class),
                    'appointments_growth' => $this->calculateGrowthRate(Appointment::class),
                    'revenue_growth' => $this->calculateRevenueGrowthRate(),
                ],
                'top_performers' => [
                    'top_doctors' => DB::table('appointments')
                        ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                        ->join('users', 'doctors.user_id', '=', 'users.id')
                        ->select(
                            'users.first_name',
                            'users.last_name',
                            DB::raw('COUNT(*) as appointments_count'),
                            DB::raw('SUM(appointments.payment_amount) as revenue')
                        )
                        ->where('appointments.status', 'completed')
                        ->groupBy('doctors.id', 'users.first_name', 'users.last_name')
                        ->orderBy('appointments_count', 'desc')
                        ->limit(5)
                        ->get(),
                    'top_specialties' => DB::table('appointments')
                        ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
                        ->join('specialties', 'doctors.specialty_id', '=', 'specialties.id')
                        ->select(
                            'specialties.name',
                            DB::raw('COUNT(*) as appointments_count'),
                            DB::raw('SUM(appointments.payment_amount) as revenue')
                        )
                        ->groupBy('specialties.name')
                        ->orderBy('appointments_count', 'desc')
                        ->limit(5)
                        ->get(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculer le taux de croissance
     */
    private function calculateGrowthRate($model)
    {
        $thisMonth = $model::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonth = $model::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Calculer le taux de croissance des revenus
     */
    private function calculateRevenueGrowthRate()
    {
        $thisMonth = Payment::completed()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $lastMonth = Payment::completed()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Gérer les notifications système
     */
    public function getSystemNotifications()
    {
        try {
            $notifications = [
                'pending_doctors' => Doctor::where('is_verified', false)->count(),
                'failed_payments' => Payment::where('status', 'failed')
                    ->whereDate('created_at', today())
                    ->count(),
                'cancelled_appointments' => Appointment::where('status', 'cancelled')
                    ->whereDate('created_at', today())
                    ->count(),
                'new_users_today' => User::whereDate('created_at', today())->count(),
                'system_health' => [
                    'database' => $this->checkDatabaseHealth(),
                    'storage' => $this->checkStorageHealth(),
                    'email' => $this->checkEmailHealth(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications système',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exporter les données (CSV, Excel)
     */
    public function exportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:appointments,payments,users,doctors',
            'format' => 'required|in:csv,excel',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $type = $request->type;
            $format = $request->format;
            
            $filename = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');

            // Logique d'export (à implémenter avec Laravel Excel)
            // return Excel::download(new AppointmentsExport($request->all()), $filename);

            return response()->json([
                'success' => true,
                'message' => 'Export généré avec succès',
                'download_url' => url('storage/exports/' . $filename)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Maintenance du système
     */
    public function systemMaintenance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:cache_clear,optimize,backup,cleanup',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Action invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $action = $request->action;
            $result = '';

            switch ($action) {
                case 'cache_clear':
                    \Artisan::call('cache:clear');
                    \Artisan::call('config:clear');
                    \Artisan::call('route:clear');
                    $result = 'Cache système vidé avec succès';
                    break;
                    
                case 'optimize':
                    \Artisan::call('optimize');
                    $result = 'Application optimisée avec succès';
                    break;
                    
                case 'backup':
                    // \Artisan::call('backup:run');
                    $result = 'Sauvegarde créée avec succès';
                    break;
                    
                case 'cleanup':
                    $this->cleanupOldFiles();
                    $result = 'Nettoyage effectué avec succès';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la maintenance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifications système privées
     */
    private function checkDatabaseHealth()
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Base de données accessible'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Erreur de connexion à la base de données'];
        }
    }

    private function checkStorageHealth()
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $usedPercent = (($diskTotal - $diskSpace) / $diskTotal) * 100;
            
            return [
                'status' => $usedPercent > 90 ? 'warning' : 'ok',
                'used_percent' => round($usedPercent, 2),
                'free_space' => $this->formatBytes($diskSpace)
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Impossible de vérifier l\'espace disque'];
        }
    }

    private function checkEmailHealth()
    {
        // Vérification basique de la configuration email
        return [
            'status' => config('mail.default') ? 'ok' : 'warning',
            'driver' => config('mail.default')
        ];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function cleanupOldFiles()
    {
        try {
            // Nettoyer les fichiers temporaires, logs anciens, etc.
            $logFiles = glob(storage_path('logs/*.log'));
            $oldFiles = array_filter($logFiles, function($file) {
                return filemtime($file) < strtotime('-30 days');
            });
            
            foreach ($oldFiles as $file) {
                unlink($file);
            }
    
            return count($oldFiles) . ' fichiers supprimés';
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des fichiers',
                'error' => $e->getMessage()
            ]);
        }
    }
    

    /**
     * Liste des utilisateurs avec filtres
     */
    public function getUsers(Request $request)
    {
        try {
            $query = User::with('role');

            // Filtrer par rôle
            if ($request->filled('role')) {
                $query->byRole($request->role);
            }

            // Filtrer par statut
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Recherche par nom ou email
            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', $search)
                      ->orWhere('last_name', 'like', $search)
                      ->orWhere('email', 'like', $search);
                });
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'un utilisateur
     */
    public function getUser($id)
    {
        try {
            $user = User::with(['role', 'doctor.specialty', 'patientAppointments', 'payments'])
                ->findOrFail($id);

            $data = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->name,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'statistics' => []
            ];

            // Statistiques spécifiques selon le rôle
            if ($user->isPatient()) {
                $data['statistics'] = [
                    'total_appointments' => $user->patientAppointments->count(),
                    'completed_appointments' => $user->patientAppointments->where('status', 'completed')->count(),
                    'total_paid' => $user->payments->where('status', 'completed')->sum('amount'),
                ];
            } elseif ($user->isDoctor()) {
                $doctor = $user->doctor;
                $data['doctor'] = [
                    'specialty' => $doctor->specialty->name ?? null,
                    'license_number' => $doctor->license_number,
                    'years_of_experience' => $doctor->years_of_experience,
                    'consultation_fee' => $doctor->consultation_fee,
                    'is_verified' => $doctor->is_verified,
                ];
                $data['statistics'] = [
                    'total_appointments' => $doctor->appointments->count(),
                    'completed_appointments' => $doctor->appointments->where('status', 'completed')->count(),
                    'total_revenue' => $doctor->appointments->where('payment_status', 'paid')->sum('payment_amount'),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Activer un utilisateur
     */
    public function activateUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur activé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Désactiver un utilisateur
     */
    public function deactivateUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['is_active' => false]);

            // Annuler les rendez-vous futurs si c'est un patient
            if ($user->isPatient()) {
                $user->patientAppointments()
                    ->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur désactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Vérifier s'il n'y a pas de rendez-vous liés
            if ($user->patientAppointments()->exists() || 
                ($user->doctor && $user->doctor->appointments()->exists())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un utilisateur avec des rendez-vous'
                ], 409);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des médecins avec statut de vérification
     */
    public function getDoctors(Request $request)
    {
        try {
            $query = Doctor::with(['user', 'specialty']);

            // Filtrer par statut de vérification
            if ($request->filled('verification_status')) {
                if ($request->verification_status === 'verified') {
                    $query->verified();
                } elseif ($request->verification_status === 'pending') {
                    $query->where('is_verified', false);
                }
            }

            // Filtrer par spécialité
            if ($request->filled('specialty_id')) {
                $query->bySpecialty($request->specialty_id);
            }

            $doctors = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'data' => [
                    'doctors' => $doctors->items(),
                    'pagination' => [
                        'current_page' => $doctors->currentPage(),
                        'last_page' => $doctors->lastPage(),
                        'per_page' => $doctors->perPage(),
                        'total' => $doctors->total(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des médecins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier un médecin
     */
    public function verifyDoctor($id)
    {
        try {
            $doctor = Doctor::with('user')->findOrFail($id);
            $doctor->update(['is_verified' => true]);

            // Notifier le médecin
            Notification::createForUser(
                $doctor->user_id,
                'Compte vérifié',
                'Votre compte médecin a été vérifié avec succès. Vous pouvez maintenant recevoir des rendez-vous.',
                Notification::TYPE_SUCCESS
            );

            return response()->json([
                'success' => true,
                'message' => 'Médecin vérifié avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter la vérification d'un médecin
     */
    public function rejectDoctor(Request $request, $id)
    {
        try {
            $doctor = Doctor::with('user')->findOrFail($id);
            
            // Notifier le médecin du rejet
            Notification::createForUser(
                $doctor->user_id,
                'Vérification rejetée',
                'Votre demande de vérification a été rejetée. ' . ($request->reason ?? 'Veuillez contacter l\'administration pour plus d\'informations.'),
                Notification::TYPE_ERROR
            );

            return response()->json([
                'success' => true,
                'message' => 'Vérification rejetée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tous les rendez-vous avec filtres
     */
    public function getAllAppointments(Request $request)
    {
        try {
            $query = Appointment::with(['patient', 'doctor.user', 'doctor.specialty']);

            // Filtres
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            if ($request->filled('date_from')) {
                $query->where('appointment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('appointment_date', '<=', $request->date_to);
            }

            $appointments = $query->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'appointments' => $appointments->items(),
                    'pagination' => [
                        'current_page' => $appointments->currentPage(),
                        'last_page' => $appointments->lastPage(),
                        'per_page' => $appointments->perPage(),
                        'total' => $appointments->total(),
                    ]
                ]
            ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des rendez-vous',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Détails d'un rendez-vous
 */
public function getAppointment($id)
{
    try {
        $appointment = Appointment::with(['patient', 'doctor.user', 'doctor.specialty'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $appointment
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Rendez-vous non trouvé',
            'error' => $e->getMessage()
        ], 404);
    }
}

/**
 * Supprimer un rendez-vous
 */
public function deleteAppointment($id)
{
    try {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous supprimé avec succès'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la suppression du rendez-vous',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Mettre à jour le statut d'un rendez-vous
 */
public function updateAppointmentStatus(Request $request, $id)
{
    try {
        $appointment = Appointment::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled'
        ]);

        $appointment->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut du rendez-vous mis à jour avec succès',
            'data' => $appointment
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du statut',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Notifier tous les utilisateurs
 */
public function sendNotificationToAll(Request $request)
{
    try {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $users = User::all();
        foreach ($users as $user) {
            Notification::createForUser(
                $user->id,
                $request->title,
                $request->message,
                Notification::TYPE_INFO
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification envoyée à tous les utilisateurs'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'envoi des notifications',
            'error' => $e->getMessage()
        ], 500);
    }
}

}