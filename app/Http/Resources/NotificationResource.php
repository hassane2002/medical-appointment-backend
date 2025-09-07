<?php

// app/Http/Resources/NotificationResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'type_color' => $this->type_color,
            'is_read' => $this->is_read,
            'related_appointment' => $this->whenLoaded('appointment', function() {
                return [
                    'id' => $this->appointment->id,
                    'reference_number' => $this->appointment->reference_number,
                    'date' => $this->appointment->appointment_date->format('d/m/Y'),
                    'time' => $this->appointment->appointment_time->format('H:i'),
                ];
            }),
            'created_at' => $this->created_at,
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}