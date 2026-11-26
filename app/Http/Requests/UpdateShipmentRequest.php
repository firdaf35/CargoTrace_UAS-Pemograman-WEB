<?php

namespace App\Http\Requests;

use App\Enums\TransportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Note: 'status' is deliberately absent. Status only changes through validated tracking events.
 */
class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');

        return $shipment && ($this->user()?->can('update', $shipment) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'transport_mode' => ['sometimes', 'required', Rule::in(TransportMode::values())],
            'vessel_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'container_number' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'flight_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'airline' => ['sometimes', 'nullable', 'string', 'max:100'],
            'vehicle_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'driver_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'customer_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'carrier_id' => ['sometimes', 'required', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)],
            'origin_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'destination_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'cargo_type' => ['sometimes', 'required', 'string', 'max:100'],
            'weight_kg' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:10000000'],
            'volume_cbm' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10000000'],
            'expected_departure' => ['sometimes', 'required', 'date'],
            'expected_arrival' => ['sometimes', 'required', 'date'],
            'estimated_arrival' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $shipment = $this->route('shipment');
            $dep = $this->input('expected_departure', $shipment->expected_departure);
            $arr = $this->input('expected_arrival', $shipment->expected_arrival);
            $origin = $this->input('origin_id', $shipment->origin_id);
            $dest = $this->input('destination_id', $shipment->destination_id);

            if ($dep && $arr && strtotime((string) $arr) <= strtotime((string) $dep)) {
                $validator->errors()->add('expected_arrival', 'Expected arrival must be after expected departure.');
            }
            if ((int) $origin === (int) $dest) {
                $validator->errors()->add('destination_id', 'Destination must differ from origin.');
            }
        }];
    }
}
