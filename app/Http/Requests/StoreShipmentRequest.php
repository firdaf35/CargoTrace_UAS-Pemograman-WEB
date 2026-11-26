<?php

namespace App\Http\Requests;

use App\Enums\TransportMode;
use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Shipment::class) ?? false;
    }

    /** Drop blank item rows submitted by the form before validating. */
    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (is_array($items)) {
            $this->merge(['items' => array_values(array_filter($items, fn ($i) => is_array($i) && filled($i['description'] ?? null)))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'transport_mode' => ['nullable', Rule::in(TransportMode::values())],
            'vessel_name' => ['nullable', 'string', 'max:100'],
            'container_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'flight_number' => ['nullable', 'string', 'max:20'],
            'airline' => ['nullable', 'string', 'max:100'],
            'vehicle_number' => ['nullable', 'string', 'max:30'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'customer')],
            'carrier_id' => ['required', 'integer', Rule::exists('carriers', 'id')->where('is_active', true)],
            'origin_id' => ['required', 'integer', 'exists:locations,id'],
            'destination_id' => ['required', 'integer', 'exists:locations,id', 'different:origin_id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after:expected_departure'],
            'estimated_arrival' => ['nullable', 'date', 'after:expected_departure'],
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
