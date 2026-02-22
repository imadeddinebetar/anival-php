<?php

namespace Core\Queue\Internal\Events;

class JobEvent
{
    public array $job;

    public function __construct(array $job)
    {
        $this->job = $job;
    }
}
