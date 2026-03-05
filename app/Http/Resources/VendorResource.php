<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'shop_name' => $this->shop_name,
            'description' => $this->description,
            'status' => $this->status,
            'logo' => $this->logo,
            'image_url' => $this->image_url,
        ];
    }
}
