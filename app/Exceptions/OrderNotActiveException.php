<?php

namespace App\Exceptions;

class OrderNotActiveException  extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Можно обновлять только активные заказы',
            422,
        );
    }
}


