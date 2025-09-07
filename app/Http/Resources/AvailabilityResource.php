<?php

// app/Http/Resources/AvailabilityResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'formatted_schedule' => $this->formatted_schedule,
            'is_available' => $this->is_available,
        ];
    }
}
