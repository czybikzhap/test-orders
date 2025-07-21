<?php

namespace App\Exceptions;

class OrderCannotBeCanceledException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Заказ не может быть отменен',
            422,
        );
    }
}
