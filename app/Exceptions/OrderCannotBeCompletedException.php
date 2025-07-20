<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderCannotBeCompletedException extends ApiException
{
    protected $message = 'Заказ не может быть завершен';
    protected $code = 422;

}
