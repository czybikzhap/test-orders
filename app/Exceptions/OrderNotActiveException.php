<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Exception;

class OrderNotActiveException extends Exception
{
    protected $message = 'Можно обновлять только активные заказы';
    protected $code = 400;

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->getCode()
        ], $this->getCode());
    }
}
