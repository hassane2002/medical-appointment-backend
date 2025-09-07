<?php

// app/Http/Resources/PaymentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'patient' => new UserResource($this->whenLoaded('patient')),
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'gateway_label' => $this->gateway_label,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'gateway_payment_intent_id' => $this->gateway_payment_intent_id,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'gateway_response' => $this->when($request->user() && $request->user()->isAdmin(), $this->gateway_response),
            'can_be_refunded' => $this->canBeRefunded(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}