<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
 /**
 * Connexion d'un utilisateur - VERSION CORRIGÉE
 */
public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
        'user_type' => 'required|in:patient,doctor,admin'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Données invalides',
            'errors' => $validator->errors()
        ], 422);
    }

    // Mapper user_type vers role_id
    $roleMap = [
        'patient' => 1,
        'doctor' => 2,
        'admin' => 3
    ];

    $roleId = $roleMap[$request->user_type];

    // Chercher l'utilisateur avec email ET role_id (pas user_type)
    $user = User::with('role', 'doctor.specialty') // Charger les relations
                ->where('email', $request->email)
                ->where('role_id', $roleId)
                ->where('is_active', true) // Vérifier que l'utilisateur est actif
                ->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Identifiants incorrects ou type d\'utilisateur invalide'
        ], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Connexion réussie',
        'user' => $user,
        'token' => $token
    ]);
}

/**
 * Inscription d'un nouvel utilisateur - VERSION CORRIGÉE
 */
public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'phone' => 'nullable|string|max:20',
        'user_type' => 'required|in:patient,doctor,admin',
        'city' => 'nullable|string|max:100',
        'specialty_id' => 'required_if:user_type,doctor|exists:specialties,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $validator->errors()
        ], 422);
    }

    // Mapper user_type vers role_id
    $roleMap = [
        'patient' => 1,
        'doctor' => 2,
        'admin' => 3
    ];

    $user = User::create([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'phone' => $request->phone,
        'city' => $request->city,
        'role_id' => $roleMap[$request->user_type],
        'is_active' => true,
    ]);

    // Si c'est un médecin, créer l'entrée dans la table doctors
    if ($request->user_type === 'doctor') {
        $user->doctor()->create([
            'specialty_id' => $request->specialty_id,
            'is_verified' => false, // Le médecin doit être vérifié par un admin
        ]);
    }

    // Charger les relations pour la réponse
    $user->load('role', 'doctor.specialty');

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Inscription réussie',
        'user' => $user,
        'token' => $token
    ], 201);
}
    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'speciality' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only(['name', 'phone', 'speciality']));

        return response()->json([
            'message' => 'Profil mis à jour',
            'user' => $user
        ]);
    }

    /**
     * Obtenir tous les utilisateurs (Admin seulement)
     */
    public function getAllUsers()
    {
        $users = User::all();
        return response()->json($users);
    }
}