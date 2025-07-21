<?php

namespace App\Exceptions;

class OrderCannotBeCompletedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Заказ не может быть завершен',
            422,
        );
    }
}
