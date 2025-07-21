<?php

namespace App\Exceptions;

class OrderCannotBeResumedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Заказ не может быть возобновлен',
            422,
        );
    }
}

