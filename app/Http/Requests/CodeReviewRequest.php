<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CodeReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // No auth needed – stateless API
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'language' => [
                'required',
                'string',
                'in:php,laravel,javascript,python',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'  => 'Code snippet is required',
            'code.min'       => 'Code must be at least 10 characters',
            'code.max'       => 'Code cannot exceed 5000 characters',
            'language.in'    => 'Language must be: php, laravel, javascript, or python',
        ];
    }
}
