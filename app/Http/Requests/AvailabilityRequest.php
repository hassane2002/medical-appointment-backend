<?php

// app/Http/Requests/AvailabilityRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AvailabilityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_available' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'day_of_week.required' => 'Le jour de la semaine est obligatoire.',
            'day_of_week.integer' => 'Le jour de la semaine doit être un nombre entier.',
            'day_of_week.between' => 'Le jour de la semaine doit être entre 0 (dimanche) et 6 (samedi).',
            'start_time.required' => 'L\'heure de début est obligatoire.',
            'start_time.date_format' => 'L\'heure de début doit être au format HH:MM.',
            'end_time.required' => 'L\'heure de fin est obligatoire.',
            'end_time.date_format' => 'L\'heure de fin doit être au format HH:MM.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
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