<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->warehouse->name,
            'stock' => $this->stock,
        ];
    }
}
