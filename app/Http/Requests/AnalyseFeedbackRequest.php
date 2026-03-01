<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class AnalyseFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feedback_text' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (trim((string) $value) === '') {
                        $fail('The feedback text cannot be empty or whitespace only.');
                    }
                },
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            new JsonResponse(
                ['message' => $validator->errors()->first()],
                400
            )
        );
    }
}
