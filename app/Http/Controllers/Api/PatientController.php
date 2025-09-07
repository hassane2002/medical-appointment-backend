<?php
// app/Http/Controllers/Api/PatientController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class PatientController extends Controller
{
    /**
     * Profil du patient connecté
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            $user->load('role');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'address' => $user->address,
                    'city' => $user->city,
                    'full_name' => $user->full_name,
                    'role' => $user->role->name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le profil du patient
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed',
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

            // Vérifier le mot de passe actuel si fourni
            if ($request->filled('current_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Mot de passe actuel incorrect'
                    ], 400);
                }
            }

            $updateData = $request->only([
                'first_name', 'last_name', 'phone', 
                'date_of_birth', 'address', 'city'
            ]);

            // Mettre à jour le mot de passe si fourni
            if ($request->filled('new_password')) {
                $updateData['password'] = Hash::make($request->new_password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'address' => $user->address,
                    'city' => $user->city,
                    'full_name' => $user->full_name,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher des médecins
     */
    public function searchDoctors(Request $request)
    {
        try {
            $query = Doctor::with(['user', 'specialty'])
                ->where('is_verified', true);

            // Filtrer par spécialité
            if ($request->filled('specialty_id')) {
                $query->where('specialty_id', $request->specialty_id);
            }

            // Filtrer par nom
            if ($request->filled('name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $searchTerm = '%' . $request->name . '%';
                    $q->where('first_name', 'like', $searchTerm)
                      ->orWhere('last_name', 'like', $searchTerm);
                });
            }

            // Filtrer par ville
            if ($request->filled('city')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            // Filtrer par prix maximum
            if ($request->filled('max_price')) {
                $query->where('consultation_fee', '<=', $request->max_price);
            }

            $doctors = $query->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Médecins trouvés',
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
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les disponibilités d'un médecin
     */
    public function getDoctorAvailability(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Date invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $doctor = Doctor::with(['user', 'specialty'])->findOrFail($doctorId);

            if (!$doctor->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce médecin n\'est pas encore vérifié'
                ], 403);
            }

            $date = $request->date;
            $availableSlots = $doctor->getAvailableSlots($date);

            return response()->json([
                'success' => true,
                'message' => 'Disponibilités récupérées',
                'data' => [
                    'doctor' => [
                        'id' => $doctor->id,
                        'name' => $doctor->user->full_name,
                        'specialty' => $doctor->specialty->name,
                        'consultation_fee' => $doctor->consultation_fee,
                        'formatted_fee' => $doctor->formatted_fee,
                    ],
                    'date' => $date,
                    'available_slots' => $availableSlots,
                    'slots_count' => count($availableSlots)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des disponibilités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques du patient
     */
    public function getStatistics(Request $request)
    {
        try {
            $user = $request->user();

            $stats = [
                'total_appointments' => $user->patientAppointments()->count(),
                'upcoming_appointments' => $user->patientAppointments()
                    ->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'completed_appointments' => $user->patientAppointments()
                    ->where('status', 'completed')
                    ->count(),
                'cancelled_appointments' => $user->patientAppointments()
                    ->where('status', 'cancelled')
                    ->count(),
                'total_spent' => $user->payments()
                    ->where('status', 'completed')
                    ->sum('amount'),
                'pending_payments' => $user->payments()
                    ->where('status', 'pending')
                    ->count(),
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