<?php

// app/Http/Requests/UpdateProfileRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->user()->id;
        
        return [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:150|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'date_of_birth.date' => 'La date de naissance doit être valide.',
            'date_of_birth.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'current_password.required_with' => 'Le mot de passe actuel est obligatoire pour changer le mot de passe.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'new_password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
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
