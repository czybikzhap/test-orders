<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:active,canceled,completed',
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'customer' => ['string', 'regex:/[a-zA-Zа-яА-Я]/'],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
    public function messages(): array
    {
        return [
            'status.string' => 'Статус должен быть строкой.',
            'status.in' => 'Допустимые статусы: active, canceled, completed.',

            'warehouse_id.integer' => 'ID склада должен быть числом.',
            'warehouse_id.exists' => 'Указанный склад не существует.',

            'customer.string' => 'Поле "Клиент" должно быть строкой',
            'customer.regex' => 'Поле "Клиент" должно содержать только буквы',

            'per_page.integer' => 'Количество элементов на странице должно быть числом.',
            'per_page.min' => 'Количество элементов на странице должно быть не менее 1.',
            'per_page.max' => 'Количество элементов на странице должно быть не более 100.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422)
        );
    }

}
