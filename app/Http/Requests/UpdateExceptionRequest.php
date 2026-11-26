<?php

namespace App\Http\Requests;

use App\Enums\ExceptionStatus;
use App\Enums\Severity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exception = $this->route('exception');

        return $exception && ($this->user()?->can('manageExceptions', $exception->shipment) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(ExceptionStatus::values())],
            'severity' => ['sometimes', 'required', Rule::in(Severity::values())],
            'resolution_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
