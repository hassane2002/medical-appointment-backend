<?php

// app/Http/Resources/SpecialtyResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecialtyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'consultation_price' => $this->consultation_price,
            'formatted_price' => $this->formatted_price,
            'doctors_count' => $this->whenCounted('doctors'),
            'verified_doctors_count' => $this->when(isset($this->verified_doctors_count), $this->verified_doctors_count),
            'doctors' => DoctorResource::collection($this->whenLoaded('doctors')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}