<?php

// app/Http/Requests/SpecialtyRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SpecialtyRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $specialtyId = $this->route('specialty') ?? $this->route('id');
        
        return [
            'name' => 'required|string|max:100|unique:specialties,name,' . $specialtyId,
            'description' => 'nullable|string',
            'consultation_price' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Le nom de la spécialité est obligatoire.',
            'name.unique' => 'Cette spécialité existe déjà.',
            'consultation_price.required' => 'Le prix de consultation est obligatoire.',
            'consultation_price.numeric' => 'Le prix doit être un nombre.',
            'consultation_price.min' => 'Le prix ne peut pas être négatif.',
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