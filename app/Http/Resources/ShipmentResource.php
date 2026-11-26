<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Shipment */
class ShipmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_number' => $this->shipment_number,
            'status' => $this->status->value,
            'display_status' => $this->display_status,
            'transport_mode' => $this->mode()->value,
            'asset' => $this->assetDetails(),
            'customer' => $this->whenLoaded('customer', fn () => ['id' => $this->customer->id, 'name' => $this->customer->name, 'company' => $this->customer->company]),
            'carrier' => $this->whenLoaded('carrier', fn () => ['id' => $this->carrier->id, 'name' => $this->carrier->name]),
            'origin' => $this->whenLoaded('origin'),
            'destination' => $this->whenLoaded('destination'),
            'cargo_type' => $this->cargo_type,
            'weight_kg' => $this->weight_kg,
            'volume_cbm' => $this->volume_cbm,
            'expected_departure' => $this->expected_departure,
            'expected_arrival' => $this->expected_arrival,
            'estimated_arrival' => $this->estimated_arrival,
            'actual_arrival' => $this->actual_arrival,
            'delay' => [
                'is_delayed' => $this->is_delayed,
                'hours' => $this->delay_hours,
                'severity' => $this->delay_severity?->value,
                'reason' => $this->delay_reason,
            ],
            'items' => $this->whenLoaded('items'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
