<?php

namespace App\Exceptions;


class OrderNotActiveException extends ApiException
{
    protected $message = 'Можно обновлять только активные заказы';
    protected $statusCode = 422;
}
