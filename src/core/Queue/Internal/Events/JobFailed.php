<?php

namespace Core\Queue\Internal\Events;

class JobFailed extends JobEvent
{
    public \Exception $exception;

    public function __construct(array $job, \Exception $exception)
    {
        parent::__construct($job);
        $this->exception = $exception;
    }
}
