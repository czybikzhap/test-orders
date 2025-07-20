<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderCannotBeCompletedException extends ApiException
{
    protected $message = 'Заказ не может быть завершен';
    protected $code = 422;

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
