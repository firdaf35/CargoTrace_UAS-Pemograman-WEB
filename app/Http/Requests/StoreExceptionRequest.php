<?php

namespace App\Http\Requests;

use App\Enums\ExceptionType;
use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shipment = $this->route('shipment');

        return $shipment && ($this->user()?->can('manageExceptions', $shipment) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ExceptionType::values())],
            'severity' => ['required', Rule::in(Severity::values())],
            'description' => ['nullable', 'string', 'max:1000'],
            'detected_at' => ['nullable', 'date'],
        ];
    }
}
