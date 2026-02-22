<?php

namespace Core\Console\Commands;

use Core\Console\Scheduling\Schedule;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class ScheduleRunCommand extends Command
{
    protected string $name = 'schedule:run';
    protected string $description = 'Run the scheduled commands';
    protected Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    public function handle(array $args, array $options): int
    {
        $this->info('Running scheduled tasks...');

        $schedule = new Schedule();

        // Load application's scheduled tasks
        $this->loadScheduledTasks($schedule);

        $events = $schedule->dueEvents($this->app);

        if (empty($events)) {
            $this->info('No scheduled commands are ready to run.');
            return 0;
        }

        foreach ($events as $event) {
            $this->info("Running scheduled task: " . date('Y-m-d H:i:s'));
            $event->run($this->app);
        }

        return 0;
    }

    protected function loadScheduledTasks(Schedule $schedule): void
    {
        $kernelClass = 'App\\Console\\Kernel';
        
        if (class_exists($kernelClass)) {
            $kernel = new $kernelClass();
            if (method_exists($kernel, 'schedule')) {
                $kernel->schedule($schedule);
            }
        }
    }
}
