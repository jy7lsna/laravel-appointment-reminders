<?php

namespace App\Http\Requests;

use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{6,30}$/'],
            'starts_at' => ['required', 'date'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<int, Closure(Validator): void> */
    public function after(AvailabilityService $availability): array
    {
        return [
            function (Validator $validator) use ($availability) {
                if ($validator->errors()->has('starts_at')) {
                    return;
                }

                $startsAt = CarbonImmutable::parse($this->input('starts_at'))->utc();

                $message = match (true) {
                    $startsAt->lt(now()) => 'That time has already passed, please pick another.',
                    ! $availability->isScheduledTime($this->route('service'), $startsAt) => 'That time is outside our available hours, please pick another.',
                    $startsAt->lt($availability->earliestBookableTime()) => 'That time is too soon to book, please pick a later one.',
                    default => null,
                };

                if ($message) {
                    $validator->errors()->add('starts_at', $message);
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.required' => 'Please pick a time slot.',
            'customer_phone.regex' => 'Please enter a valid phone number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'name',
            'customer_email' => 'email',
            'customer_phone' => 'phone',
        ];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('starts_at'))->utc();
    }

    /** @return array{customer_name: string, customer_email: string, customer_phone: string} */
    public function customer(): array
    {
        return [
            'customer_name' => trim($this->validated('customer_name')),
            'customer_email' => strtolower(trim($this->validated('customer_email'))),
            'customer_phone' => trim($this->validated('customer_phone')),
        ];
    }
}
