<?php

namespace Core\Queue\Contracts;

/**
 * Marker interface: when a job class implements this,
 * it should be dispatched to the queue instead of running synchronously.
 *
 * Usage:
 *   class SendWelcomeEmail implements ShouldQueue
 *   {
 *       use Dispatchable, Queueable;
 *       ...
 *   }
 */
interface ShouldQueue
{
    //
}
