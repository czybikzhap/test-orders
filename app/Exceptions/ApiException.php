<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

abstract class ApiException extends Exception
{
    /**
     * Абстрактный метод для формирования JSON-ответа
     *
     * @param Request $request
     * @return JsonResponse
     */
    abstract public function render(Request $request): JsonResponse;
}
