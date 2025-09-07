<?php
// app/Http/Requests/RegisterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:patient,doctor',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email existe déjà.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être patient ou médecin.',
            'date_of_birth.date' => 'La date de naissance doit être une date valide.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $validator->errors()
        ], 422));
    }
}

// ===================================================================


// ===================================================================

// app/Http/Requests/PaymentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'payment_method' => 'required|string',
            'payment_method_id' => 'required_if:gateway,stripe|string',
            'simulate_success' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'appointment_id.required' => 'Le rendez-vous est obligatoire.',
            'appointment_id.exists' => 'Le rendez-vous sélectionné n\'existe pas.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',
            'payment_method_id.required_if' => 'L\'ID de la méthode de paiement est obligatoire pour Stripe.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $validator->errors()
        ], 422));
    }
}

// ===================================================================



