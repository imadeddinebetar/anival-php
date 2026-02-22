<?php

namespace Core\Console\Scheduling;

/**
 * @internal
 */
class Schedule
{
    /** @var array<int, CallbackEvent> */
    protected array $events = [];

    public function call(callable $callback): CallbackEvent
    {
        $event = new CallbackEvent($callback);
        $this->events[] = $event;
        return $event;
    }

    public function command(string $command): CallbackEvent
    {
         return $this->call(function ($app) use ($command) {
             // Basic command runner logic, for now just echoing
             // In a real app this would call the Kernel/Console Application
             echo "Running scheduled command: {$command}\n";
         });
    }

    /** @return array<int, CallbackEvent> */
    public function dueEvents(mixed $app): array
    {
        return array_filter($this->events, function ($event) {
            return $event->isDue();
        });
    }
}
