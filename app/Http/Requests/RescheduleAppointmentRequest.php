<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return ['starts_at.required' => 'Please pick a new time slot.'];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('starts_at'))->utc();
    }
}
