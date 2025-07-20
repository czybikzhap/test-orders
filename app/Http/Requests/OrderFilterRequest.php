<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'customer' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
