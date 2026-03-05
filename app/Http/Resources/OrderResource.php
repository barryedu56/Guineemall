<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'payment_status' => $this->payment_status,
            'user' => new UserResource($this->whenLoaded('user')),
            'vendor_orders' => VendorOrderResource::collection($this->whenLoaded('vendorOrders')),
        ];
    }
}
