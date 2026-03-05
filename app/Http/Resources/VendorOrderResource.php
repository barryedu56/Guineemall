<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'vendor_id' => $this->vendor_id,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
        ];
    }
}
