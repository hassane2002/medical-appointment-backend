<?php

// app/Http/Requests/AppointmentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppointmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
            'payment_method' => 'required|in:online,cabinet',
        ];
    }

    public function messages()
    {
        return [
            'doctor_id.required' => 'Le médecin est obligatoire.',
            'doctor_id.exists' => 'Le médecin sélectionné n\'existe pas.',
            'appointment_date.required' => 'La date du rendez-vous est obligatoire.',
            'appointment_date.date' => 'La date doit être valide.',
            'appointment_date.after_or_equal' => 'La date ne peut pas être dans le passé.',
            'appointment_time.required' => 'L\'heure du rendez-vous est obligatoire.',
            'appointment_time.date_format' => 'L\'heure doit être au format HH:MM.',
            'reason.max' => 'La raison ne peut pas dépasser 500 caractères.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
            'payment_method.in' => 'Le mode de paiement doit être en ligne ou au cabinet.',
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