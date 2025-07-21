<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price' => $this->product->price,
            ],
            'warehouse' => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ],
            'quantity_change' => $this->quantity_change,
            'movement_type' => $this->movement_type,
            'movement_type_label' => $this->getMovementTypeLabel(),
            'description' => $this->description,
            'stock_before' => $this->stock_before,
            'stock_after' => $this->stock_after,
            'order' => $this->order ? [
                'id' => $this->order->id,
                'customer' => $this->order->customer,
                'status' => $this->order->status,
            ] : null,
            'created_at' => $this->created_at,
            'is_incoming' => $this->isIncoming(),
            'is_outgoing' => $this->isOutgoing(),
        ];
    }
}
