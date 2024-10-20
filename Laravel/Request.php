<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

trait Request
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitize($this->all()));
    }

    protected function sanitize(array $data): array
    {
        return array_map(function ($item) {
            return is_string($item) ? strip_tags(trim($item)) : $item;
        }, $data);
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
//            if ($this->password !== $this->password_confirmation) {
//                $validator->errors()->add('password_confirmation', 'The password confirmation does not match.');
//            } ...
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
