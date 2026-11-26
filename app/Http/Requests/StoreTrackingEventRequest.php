<?php

namespace App\Http\Requests;

use App\Models\TrackingEvent;
use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrackingEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');

        return $shipment && ($this->user()?->can('addTracking', $shipment) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $types = array_merge(
            array_diff(ShipmentStatus::values(), ['CREATED']),
            TrackingEvent::INFO_TYPES
        );

        return [
            'event_type' => ['required', 'string', Rule::in($types)],
            'occurred_at' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['nullable', 'string', 'max:1000'],
            'estimated_arrival' => ['nullable', 'date'],
        ];
    }
}
