<?php

// app/Http/Resources/DoctorResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'specialty' => $this->whenLoaded('specialty', function() {
                return [
                    'id' => $this->specialty->id,
                    'name' => $this->specialty->name,
                    'description' => $this->specialty->description,
                ];
            }),
            'license_number' => $this->license_number,
            'years_of_experience' => $this->years_of_experience,
            'consultation_fee' => $this->consultation_fee,
            'formatted_fee' => $this->formatted_fee,
            'cabinet_address' => $this->cabinet_address,
            'cabinet_phone' => $this->cabinet_phone,
            'bio' => $this->bio,
            'is_verified' => $this->is_verified,
            'availabilities' => AvailabilityResource::collection($this->whenLoaded('availabilities')),
            'statistics' => $this->when($this->statistics ?? false, function() {
                return [
                    'total_appointments' => $this->appointments_count ?? 0,
                    'completed_appointments' => $this->completed_appointments_count ?? 0,
                    'rating' => $this->average_rating ?? 0,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
