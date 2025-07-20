<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderCannotBeCanceledException extends ApiException
{
    protected $message = 'Заказ не может быть отменен';
    protected $code = 422;

}
