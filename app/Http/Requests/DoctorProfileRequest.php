<?php

// app/Http/Requests/DoctorProfileRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DoctorProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $doctorId = $this->user()->doctor->id ?? null;
        
        return [
            'specialty_id' => 'required|exists:specialties,id',
            'license_number' => 'required|string|max:50|unique:doctors,license_number,' . $doctorId,
            'years_of_experience' => 'required|integer|min:0|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'cabinet_address' => 'required|string',
            'cabinet_phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'specialty_id.required' => 'La spécialité est obligatoire.',
            'specialty_id.exists' => 'La spécialité sélectionnée n\'existe pas.',
            'license_number.required' => 'Le numéro de licence est obligatoire.',
            'license_number.unique' => 'Ce numéro de licence existe déjà.',
            'years_of_experience.required' => 'L\'expérience est obligatoire.',
            'years_of_experience.integer' => 'L\'expérience doit être un nombre entier.',
            'years_of_experience.min' => 'L\'expérience ne peut pas être négative.',
            'years_of_experience.max' => 'L\'expérience ne peut pas dépasser 50 ans.',
            'consultation_fee.required' => 'Les honoraires de consultation sont obligatoires.',
            'consultation_fee.numeric' => 'Les honoraires doivent être un nombre.',
            'consultation_fee.min' => 'Les honoraires ne peuvent pas être négatifs.',
            'cabinet_address.required' => 'L\'adresse du cabinet est obligatoire.',
            'bio.max' => 'La biographie ne peut pas dépasser 1000 caractères.',
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
