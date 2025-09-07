<?php

// app/Http/Resources/AppointmentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'patient' => new UserResource($this->whenLoaded('patient')),
            'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'appointment_time' => $this->appointment_time->format('H:i'),
            'formatted_date_time' => $this->full_date_time,
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->payment_status_label,
            'payment_amount' => $this->payment_amount,
            'formatted_amount' => $this->formatted_amount,
            'notes' => $this->notes,
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'receipt' => $this->whenLoaded('receipt', function() {
                return [
                    'id' => $this->receipt->id,
                    'receipt_number' => $this->receipt->receipt_number,
                    'pdf_url' => $this->receipt->pdf_url,
                    'generated_at' => $this->receipt->generated_at,
                ];
            }),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_confirmed' => $this->canBeConfirmed(),
            'can_be_completed' => $this->canBeCompleted(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}