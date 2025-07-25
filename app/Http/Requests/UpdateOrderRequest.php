<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderRequest extends FormRequest
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
            'customer' => ['string', 'regex:/[a-zA-Zа-яА-Я]/'],
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.count' => 'required_with:items|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer.string' => 'Поле "Клиент" должно быть строкой',
            'customer.regex' => 'Поле "Клиент" должно содержать только буквы',

            'items.array' => 'Поле "Товары" должно быть массивом.',
            'items.min' => 'Добавьте хотя бы один товар.',

            'items.*.product_id.required_with' => 'Поле "Товар" обязательно при наличии списка товаров.',
            'items.*.product_id.exists' => 'Указанный товар не существует.',

            'items.*.count.required_with' => 'Поле "Количество" обязательно при наличии товаров.',
            'items.*.count.integer' => 'Количество должно быть числом.',
            'items.*.count.min' => 'Количество должно быть не меньше 1.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
