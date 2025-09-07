<?php

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
