<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_movements' => $this['total_movements'],
            'total_incoming' => $this['total_incoming'],
            'total_outgoing' => $this['total_outgoing'],
            'net_change' => $this['total_incoming'] - $this['total_outgoing'],
            'movement_types' => $this['movement_types'],
        ];
    }
}
