<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        ApiException::class,
    ];

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($e);
        }

        return parent::render($request, $e);
    }

    protected function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof ApiException) {
            return $e->render();
        }

        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'error' => [
                    'code' => 'validation_failed',
                    'status' => 422,
                    'details' => $e->errors(),
                ]
            ], 422);
        }


        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Ресурс не найден',
                'error' => [
                    'code' => 'route_not_found',
                    'status' => 404,
                ]
            ], 404);
        }

        return response()->json([
            'success' => false,
            'message' => 'Внутренняя ошибка сервера',
            'error' => config('app.debug') ? [
                'code' => 'server_error',
                'status' => 500,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : [
                'code' => 'server_error',
                'status' => 500,
            ]
        ], 500);
    }
}
