<?php

namespace App\Events;

class OrderShipped
{
    public function __construct(public readonly mixed $order)
    {
    }
}
