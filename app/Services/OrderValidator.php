<?php

namespace App\Services;

use App\Models\Order;
use App\Exceptions\OrderNotActiveException;
use App\Exceptions\OrderCannotBeCompletedException;
use App\Exceptions\OrderCannotBeCanceledException;
use App\Exceptions\OrderCannotBeResumedException;

class OrderValidator
{
    /**
     * @throws OrderNotActiveException
     */
    public function ensureIsActive(Order $order): void
    {
        if (!$order->isActive()) {
            throw new OrderNotActiveException();
        }
    }

    /**
     * @throws OrderCannotBeCompletedException
     */
    public function ensureCanBeCompleted(Order $order): void
    {
        if (!$order->canBeCompleted()) {
            throw new OrderCannotBeCompletedException();
        }
    }

    /**
     * @throws OrderCannotBeCanceledException
     */
    public function ensureCanBeCanceled(Order $order): void
    {
        if (!$order->canBeCanceled()) {
            throw new OrderCannotBeCanceledException();
        }
    }

    /**
     * @throws OrderCannotBeResumedException
     */
    public function ensureCanBeResumed(Order $order): void
    {
        if (!$order->canBeResumed()) {
            throw new OrderCannotBeResumedException();
        }
    }
}
