<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
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
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.count' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer.string' => 'Поле "Клиент" должно быть строкой',
            'customer.regex' => 'Поле "Клиент" должно содержать только буквы',

            'warehouse_id.required' => 'Поле "Склад" обязательно.',
            'warehouse_id.exists' => 'Указанный склад не найден в системе.',

            'items.required' => 'Необходимо указать хотя бы один товар.',
            'items.array' => 'Поле "Товары" должно быть массивом.',
            'items.min' => 'Добавьте хотя бы один товар в заказ.',

            'items.*.product_id.required' => 'Поле "Товар" обязательно.',
            'items.*.product_id.exists' => 'Один или несколько указанных товаров не существуют.',

            'items.*.count.required' => 'Поле "Количество" обязательно.',
            'items.*.count.integer' => 'Поле "Количество" должно быть числом.',
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
