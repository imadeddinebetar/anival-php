<?php

namespace Core\Console\Internal;

use Core\Console\Commands\AppKeyCommand;
use Core\Console\Commands\ConfigCacheCommand;
use Core\Console\Commands\ConfigClearCommand;
use Core\Console\Commands\DbSeedCommand;
use Core\Console\Commands\Down;
use Core\Console\Commands\MakeCommand;
use Core\Console\Commands\MakeController;
use Core\Console\Commands\MakeJob;
use Core\Console\Commands\MakeMiddleware;
use Core\Console\Commands\MakeMigration;
use Core\Console\Commands\MakeModel;
use Core\Console\Commands\MakeRequest;
use Core\Console\Commands\MakeSeeder;
use Core\Console\Commands\MakeService;
use Core\Console\Commands\MakeTest;
use Core\Console\Commands\MigrateCommand;
use Core\Console\Commands\MigrateRollbackCommand;
use Core\Console\Commands\MigrateStatusCommand;
use Core\Console\Commands\RouteList;
use Core\Console\Commands\ScheduleRunCommand;
use Core\Console\Commands\Serve;
use Core\Console\Commands\StorageLinkCommand;
use Core\Console\Commands\StorageUnlinkCommand;
use Core\Console\Commands\TestCommand;
use Core\Console\Commands\Up;
use Core\Console\Contracts\CommandInterface;
use Core\Console\Contracts\ConsoleKernelInterface;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class ConsoleKernel implements ConsoleKernelInterface
{
    /**
     * @var array<string, class-string<CommandInterface>>
     */
    private array $commands = [];

    public function __construct(private readonly Application $app)
    {
        $this->registerCoreCommands();
        $this->registerApplicationCommands();
    }

    public function registerCommand(string $name, string $commandClass): void
    {
        $this->commands[$name] = $commandClass;
    }

    public function all(): array
    {
        ksort($this->commands);
        return $this->commands;
    }

    public function handle(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        [$filteredArgs, $options] = $this->parseArgsAndOptions($args);

        if ($command === 'help') {
            $target = $filteredArgs[0] ?? null;
            $this->showHelp($target);
            return 0;
        }

        if (!isset($this->commands[$command])) {
            echo "Unknown command: {$command}\n\n";
            $this->showHelp();
            return 1;
        }

        $commandClass = $this->commands[$command];
        /** @var CommandInterface $instance */
        $instance = $this->app->make($commandClass);

        return $instance->handle($filteredArgs, $options);
    }

    /**
     * @param array<int, string> $args
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function parseArgsAndOptions(array $args): array
    {
        $options = [];
        $filteredArgs = [];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);

                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $options[$key] = $value;
                    continue;
                }

                if (str_starts_with($option, 'no-')) {
                    $options[substr($option, 3)] = false;
                    continue;
                }

                $options[$option] = true;
                continue;
            }

            if (str_starts_with($arg, '-')) {
                $short = substr($arg, 1);
                $options[$short] = true;
                continue;
            }

            $filteredArgs[] = $arg;
        }

        return [$filteredArgs, $options];
    }

    private function registerCoreCommands(): void
    {
        $this->registerCommand('app:key', AppKeyCommand::class);
        $this->registerCommand('config:cache', ConfigCacheCommand::class);
        $this->registerCommand('config:clear', ConfigClearCommand::class);
        $this->registerCommand('route:cache', 'Core\\Console\\Commands\\RouteCacheCommand');
        $this->registerCommand('route:clear', 'Core\\Console\\Commands\\RouteClearCommand');
        $this->registerCommand('view:cache', 'Core\\Console\\Commands\\ViewCacheCommand');
        $this->registerCommand('db:seed', DbSeedCommand::class);

        $this->registerCommand('make:controller', MakeController::class);
        $this->registerCommand('make:model', MakeModel::class);
        $this->registerCommand('make:middleware', MakeMiddleware::class);
        $this->registerCommand('make:job', MakeJob::class);
        $this->registerCommand('make:migration', MakeMigration::class);
        $this->registerCommand('make:seeder', MakeSeeder::class);
        $this->registerCommand('make:service', MakeService::class);
        $this->registerCommand('make:request', MakeRequest::class);
        $this->registerCommand('make:command', MakeCommand::class);
        $this->registerCommand('make:test', MakeTest::class);

        $this->registerCommand('route:list', RouteList::class);
        $this->registerCommand('serve', Serve::class);
        $this->registerCommand('down', Down::class);
        $this->registerCommand('up', Up::class);

        $this->registerCommand('migrate', MigrateCommand::class);
        $this->registerCommand('migrate:rollback', MigrateRollbackCommand::class);
        $this->registerCommand('migrate:status', MigrateStatusCommand::class);
        $this->registerCommand('schedule:run', ScheduleRunCommand::class);

        $this->registerCommand('storage:link', StorageLinkCommand::class);
        $this->registerCommand('storage:unlink', StorageUnlinkCommand::class);
        $this->registerCommand('test', TestCommand::class);
    }

    private function registerApplicationCommands(): void
    {
        $kernelClass = 'App\\Console\\Kernel';

        if (!class_exists($kernelClass)) {
            return;
        }

        $kernel = $this->app->make($kernelClass);

        if (!method_exists($kernel, 'commands')) {
            return;
        }

        foreach ($kernel->commands() as $commandClass) {
            if (!is_string($commandClass) || !class_exists($commandClass)) {
                continue;
            }

            $command = $this->app->make($commandClass);
            if (!$command instanceof CommandInterface) {
                continue;
            }

            $name = $command instanceof \Core\Console\Commands\Command ? $command->getName() : '';
            if ($name === '') {
                continue;
            }

            $this->registerCommand($name, $commandClass);
        }
    }

    private function showHelp(?string $command = null): void
    {
        if ($command !== null && isset($this->commands[$command])) {
            $commandClass = $this->commands[$command];
            $instance = $this->app->make($commandClass);
            $description = method_exists($instance, 'getDescription') ? $instance->getDescription() : '';

            echo "Usage: php anival {$command} [options]\n";
            if ($description !== '') {
                echo "\n{$description}\n";
            }
            return;
        }

        echo "Anival Framework CLI\n";
        echo "Usage: php anival <command> [options]\n\n";
        echo "Available commands:\n\n";

        foreach ($this->all() as $name => $commandClass) {
            $instance = $this->app->make($commandClass);
            $description = method_exists($instance, 'getDescription') ? $instance->getDescription() : '';
            echo str_pad("  {$name}", 24) . $description . "\n";
        }

        echo "\n";
        echo str_pad('  help', 24) . "Show this help message\n";
    }
}
