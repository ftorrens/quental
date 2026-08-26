<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-\.]+$/'],
            'status' => ['nullable', 'string', Rule::in(['Alive', 'Dead', 'unknown'])],
            'species' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\s\-\.]+$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}