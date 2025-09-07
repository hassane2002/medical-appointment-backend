<?php
// app/Http/Controllers/Api/SpecialtyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialtyController extends Controller
{
    /**
     * Liste de toutes les spécialités
     */
    public function index()
    {
        try {
            $specialties = Specialty::with(['doctors' => function($query) {
                $query->where('is_verified', true)->with('user:id,first_name,last_name');
            }])->get();

            return response()->json([
                'success' => true,
                'message' => 'Spécialités récupérées avec succès',
                'data' => $specialties->map(function($specialty) {
                    return [
                        'id' => $specialty->id,
                        'name' => $specialty->name,
                        'description' => $specialty->description,
                        'consultation_price' => $specialty->consultation_price,
                        'formatted_price' => $specialty->formatted_price,
                        'doctors_count' => $specialty->doctors->count(),
                        'verified_doctors' => $specialty->doctors->map(function($doctor) {
                            return [
                                'id' => $doctor->id,
                                'name' => $doctor->user->full_name,
                                'experience' => $doctor->years_of_experience,
                                'consultation_fee' => $doctor->consultation_fee,
                            ];
                        })
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des spécialités',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une spécialité spécifique
     */
    public function show($id)
    {
        try {
            $specialty = Specialty::with(['doctors.user'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Spécialité trouvée',
                'data' => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'description' => $specialty->description,
                    'consultation_price' => $specialty->consultation_price,
                    'formatted_price' => $specialty->formatted_price,
                    'doctors' => $specialty->doctors->filter(function($doctor) {
                        return $doctor->is_verified;
                    })->values()->map(function($doctor) {
                        return [
                            'id' => $doctor->id,
                            'name' => $doctor->user->full_name,
                            'years_of_experience' => $doctor->years_of_experience,
                            'consultation_fee' => $doctor->consultation_fee,
                            'formatted_fee' => $doctor->formatted_fee,
                            'bio' => $doctor->bio,
                            'cabinet_address' => $doctor->cabinet_address,
                            'cabinet_phone' => $doctor->cabinet_phone,
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spécialité non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Créer une nouvelle spécialité (Admin seulement)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:specialties',
            'description' => 'nullable|string',
            'consultation_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $specialty = Specialty::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Spécialité créée avec succès',
                'data' => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'description' => $specialty->description,
                    'consultation_price' => $specialty->consultation_price,
                    'formatted_price' => $specialty->formatted_price,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une spécialité (Admin seulement)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100|unique:specialties,name,' . $id,
            'description' => 'nullable|string',
            'consultation_price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $specialty = Specialty::findOrFail($id);
            $specialty->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Spécialité mise à jour avec succès',
                'data' => [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'description' => $specialty->description,
                    'consultation_price' => $specialty->consultation_price,
                    'formatted_price' => $specialty->formatted_price,
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
     * Supprimer une spécialité (Admin seulement)
     */
    public function destroy($id)
    {
        try {
            $specialty = Specialty::findOrFail($id);

            // Vérifier s'il y a des médecins associés
            if ($specialty->doctors()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer une spécialité avec des médecins associés'
                ], 409);
            }

            $specialty->delete();

            return response()->json([
                'success' => true,
                'message' => 'Spécialité supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}