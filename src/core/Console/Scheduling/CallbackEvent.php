<?php

namespace Core\Console\Scheduling;

/**
 * @internal
 */
class CallbackEvent
{
    protected string $expression = '* * * * *';
    /** @var callable */
    protected $callback;
    protected string $description = '';

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(string $time): self
    {
        $segments = explode(':', $time);
        return $this->cron(sprintf('%d %d * * *', $segments[1], $segments[0]));
    }

    public function isDue(): bool
    {
        // Simple Cron expression parser
        $date = date('Y-m-d H:i');
        $time = strtotime($date); // Drop seconds

        $cronParts = explode(' ', $this->expression);
        $dateParts = [
            (int)date('i', $time), // Minute
            (int)date('H', $time), // Hour
            (int)date('d', $time), // Day
            (int)date('m', $time), // Month
            (int)date('w', $time), // Weekday
        ];

        foreach ($cronParts as $index => $part) {
            if ($part === '*') {
                continue;
            }

            if (str_contains($part, ',')) {
                $values = explode(',', $part);
                if (!in_array($dateParts[$index], $values)) {
                    return false;
                }
                continue;
            }

            if (str_contains($part, '/')) {
                [$start, $step] = explode('/', $part);
                $start = $start === '*' ? 0 : (int)$start;
                if (($dateParts[$index] - $start) % $step !== 0) {
                    return false;
                }
                continue;
            }

            if ((int)$part !== $dateParts[$index]) {
                return false;
            }
        }

        return true;
    }

    public function run(mixed $app): void
    {
        call_user_func($this->callback, $app);
    }
}
