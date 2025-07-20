<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        OrderStatusException::class,
    ];

    public function register(): void
    {
        $this->renderable(function (OrderStatusException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ], $e->getCode() ?: 400);
        });
    }
}
