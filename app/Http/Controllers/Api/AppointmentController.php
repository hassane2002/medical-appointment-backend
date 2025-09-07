<?php
// app/Http/Controllers/Api/AppointmentController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\AppointmentReceipt;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AppointmentController extends Controller
{
    /**
     * Rendez-vous du patient connecté
     */
    public function patientAppointments(Request $request)
    {
        try {
            $user = $request->user();
            $query = $user->patientAppointments()
                ->with(['doctor.user', 'doctor.specialty']);

            // Filtrer par statut
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filtrer par date
            if ($request->filled('date_from')) {
                $query->where('appointment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('appointment_date', '<=', $request->date_to);
            }

            $appointments = $query->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->paginate(10);

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
     * Rendez-vous du médecin connecté
     */
    public function doctorAppointments(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $query = $doctor->appointments()
                ->with(['patient', 'doctor.specialty']);

            // Filtrer par statut
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filtrer par date
            if ($request->filled('date')) {
                $query->where('appointment_date', $request->date);
            } else {
                // Par défaut, afficher les rendez-vous à partir d'aujourd'hui
                $query->where('appointment_date', '>=', now()->toDateString());
            }

            $appointments = $query->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc')
                ->paginate(15);

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
     * Réserver un rendez-vous (Patient)
     */
    public function bookAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
            'payment_method' => 'required|in:online,cabinet',
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
            $doctor = Doctor::with(['user', 'specialty'])->findOrFail($request->doctor_id);

            // Vérifier si le médecin est vérifié
            if (!$doctor->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce médecin n\'est pas encore vérifié'
                ], 403);
            }

            // Vérifier la disponibilité
            $isAvailable = $doctor->isAvailableAt($request->appointment_date, $request->appointment_time);
            if (!$isAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce créneau n\'est pas disponible'
                ], 409);
            }

            // Vérifier si le créneau n'est pas déjà pris
            $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
                ->where('appointment_date', $request->appointment_date)
                ->where('appointment_time', $request->appointment_time)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($existingAppointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce créneau est déjà réservé'
                ], 409);
            }

            // Créer le rendez-vous
            $appointment = Appointment::create([
                'patient_id' => $user->id,
                'doctor_id' => $request->doctor_id,
                'appointment_date' => $request->appointment_date,
                'appointment_time' => $request->appointment_time,
                'reason' => $request->reason,
                'payment_method' => $request->payment_method,
                'payment_amount' => $doctor->consultation_fee,
                'status' => $request->payment_method === 'online' ? 'pending' : 'pending',
            ]);

            // Créer les notifications
            Notification::notifyAppointmentBooked($appointment);

            $appointment->load(['doctor.user', 'doctor.specialty', 'patient']);

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous réservé avec succès',
                'data' => [
                    'id' => $appointment->id,
                    'reference_number' => $appointment->reference_number,
                    'doctor_name' => $appointment->doctor->user->full_name,
                    'specialty' => $appointment->doctor->specialty->name,
                    'appointment_date' => $appointment->appointment_date->format('d/m/Y'),
                    'appointment_time' => $appointment->appointment_time->format('H:i'),
                    'status' => $appointment->status,
                    'payment_method' => $appointment->payment_method,
                    'payment_amount' => $appointment->payment_amount,
                    'needs_payment' => $appointment->payment_method === 'online',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Voir un rendez-vous spécifique (Patient)
     */
    public function showPatientAppointment(Request $request, $id)
    {
        try {
            $user = $request->user();
            $appointment = $user->patientAppointments()
                ->with(['doctor.user', 'doctor.specialty', 'payment'])
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
     * Voir un rendez-vous spécifique (Médecin)
     */
    public function showDoctorAppointment(Request $request, $id)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $appointment = $doctor->appointments()
                ->with(['patient', 'payment'])
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
     * Confirmer un rendez-vous (Médecin)
     */
    public function confirmAppointment(Request $request, $id)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $appointment = $doctor->appointments()->findOrFail($id);

            if (!$appointment->canBeConfirmed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être confirmé'
                ], 409);
            }

            $appointment->confirm();

            // Créer la notification
            Notification::notifyAppointmentConfirmed($appointment);

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous confirmé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rejeter un rendez-vous (Médecin)
     */
    public function rejectAppointment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
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
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $appointment = $doctor->appointments()->findOrFail($id);

            if ($appointment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être rejeté'
                ], 409);
            }

            $appointment->update([
                'status' => 'cancelled',
                'notes' => $request->reason
            ]);

            // Créer la notification pour le patient
            Notification::createForUser(
                $appointment->patient_id,
                'Rendez-vous annulé',
                "Votre rendez-vous du {$appointment->full_date_time} a été annulé par le médecin." . 
                ($request->reason ? " Raison : {$request->reason}" : ''),
                Notification::TYPE_WARNING,
                $appointment->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous rejeté avec succès'
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
     * Annuler un rendez-vous (Patient)
     */
    public function cancelAppointment(Request $request, $id)
    {
        try {
            $user = $request->user();
            $appointment = $user->patientAppointments()->findOrFail($id);

            if (!$appointment->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être annulé'
                ], 409);
            }

            $appointment->cancel();

            // Créer la notification pour le médecin
            Notification::createForUser(
                $appointment->doctor->user_id,
                'Rendez-vous annulé',
                "Le rendez-vous avec {$appointment->patient->full_name} du {$appointment->full_date_time} a été annulé.",
                Notification::TYPE_INFO,
                $appointment->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous annulé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marquer un rendez-vous comme terminé (Médecin)
     */
    public function completeAppointment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
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
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $appointment = $doctor->appointments()->findOrFail($id);

            if (!$appointment->canBeCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rendez-vous ne peut pas être marqué comme terminé'
                ], 409);
            }

            $appointment->complete();

            if ($request->filled('notes')) {
                $appointment->update(['notes' => $request->notes]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous marqué comme terminé'
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
     * Ajouter des notes à un rendez-vous (Médecin)
     */
    public function addNotes(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000',
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
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil médecin non trouvé'
                ], 404);
            }

            $appointment = $doctor->appointments()->findOrFail($id);
            $appointment->update(['notes' => $request->notes]);

            return response()->json([
                'success' => true,
                'message' => 'Notes ajoutées avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le justificatif PDF
     */
    public function downloadReceipt(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Vérifier l'accès selon le rôle
            if ($user->isPatient()) {
                $appointment = $user->patientAppointments()
                    ->with(['doctor.user', 'doctor.specialty', 'patient'])
                    ->findOrFail($id);
            } elseif ($user->isDoctor()) {
                $doctor = $user->doctor;
                if (!$doctor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Profil médecin non trouvé'
                    ], 404);
                }
                $appointment = $doctor->appointments()
                    ->with(['doctor.user', 'doctor.specialty', 'patient'])
                    ->findOrFail($id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Générer ou récupérer le justificatif
            $receipt = $this->generateReceipt($appointment);

            // Marquer comme téléchargé
            if ($user->isPatient()) {
                $receipt->update(['downloaded_by_patient' => true]);
            } elseif ($user->isDoctor()) {
                $receipt->update(['downloaded_by_doctor' => true]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Justificatif généré avec succès',
                'data' => [
                    'receipt_number' => $receipt->receipt_number,
                    'pdf_url' => $receipt->pdf_url,
                    'generated_at' => $receipt->generated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du justificatif',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer le justificatif PDF
     */
    private function generateReceipt($appointment)
    {
        // Vérifier si le justificatif existe déjà
        $receipt = AppointmentReceipt::where('appointment_id', $appointment->id)->first();

        if (!$receipt) {
            // Générer le PDF
            $pdf = Pdf::loadView('receipts.appointment', compact('appointment'));
            
            // Sauvegarder le PDF
            $filename = 'justificatifs/appointment_' . $appointment->id . '_' . time() . '.pdf';
            Storage::disk('public')->put($filename, $pdf->output());

            // Créer l'enregistrement
            $receipt = AppointmentReceipt::create([
                'appointment_id' => $appointment->id,
                'pdf_path' => $filename,
                'qr_code' => null, // TODO: Générer QR code
            ]);
        }

        return $receipt;
    }
}