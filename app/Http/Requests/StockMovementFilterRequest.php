<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StockMovementFilterRequest extends FormRequest
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
            'warehouse_id' => 'sometimes|exists:warehouses,id',
            'product_id' => 'sometimes|exists:products,id',
            'movement_type' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.exists' => 'Указанный склад не существует.',

            'product_id.exists' => 'Указанный товар не существует.',

            'movement_type.string' => 'Тип движения должен быть строкой.',

            'date_from.date' => 'Дата начала должна быть корректной датой.',

            'date_to.date' => 'Дата окончания должна быть корректной датой.',
            'date_to.after_or_equal' => 'Дата окончания должна быть равна или позже даты начала.',

            'per_page.integer' => 'Количество элементов на странице должно быть целым числом.',
            'per_page.min' => 'Количество элементов на странице должно быть не менее 1.',
            'per_page.max' => 'Количество элементов на странице не должно превышать 100.',
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
