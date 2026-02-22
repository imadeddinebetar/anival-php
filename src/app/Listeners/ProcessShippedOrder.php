<?php

namespace App\Listeners;

use App\Events\OrderShipped;

class ProcessShippedOrder
{
    public function handle(OrderShipped $event): void
    {
        // Process the shipped order: notify customer, update records, etc.
        $order = $event->order;
    }
}
