<?php

namespace Core\Console\Commands;

use Core\Console\Contracts\CommandInterface;

/**
 * @internal
 */
abstract class Command implements CommandInterface
{
    protected string $name;
    protected string $description = '';
    protected array $options = [];
    protected array $arguments = [];

    public function __construct()
    {
        $this->name = static::class;
    }

    abstract public function handle(array $args, array $options): int;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function argument(string $name, array $args): ?string
    {
        return $args[$name] ?? null;
    }

    protected function option(string $name, array $options, mixed $default = null): mixed
    {
        return $options[$name] ?? $default;
    }

    protected function info(string $message): void
    {
        echo "\033[32mINFO:\033[0m {$message}\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31mERROR:\033[0m {$message}\n";
    }

    protected function warn(string $message): void
    {
        echo "\033[33mWARNING:\033[0m {$message}\n";
    }

    protected function line(string $message): void
    {
        echo "{$message}\n";
    }

    protected function table(array $headers, array $rows): void
    {
        $colWidths = [];

        foreach ($headers as $i => $header) {
            $colWidths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $colWidths[$i] = max($colWidths[$i] ?? 0, strlen((string) $cell));
            }
        }

        // Print header
        $line = '|';
        foreach ($headers as $i => $header) {
            $line .= ' ' . str_pad($header, $colWidths[$i]) . ' |';
        }
        echo $line . "\n";

        // Print separator
        $sep = '+';
        foreach ($colWidths as $width) {
            $sep .= '-' . str_repeat('-', $width) . '-+';
        }
        echo $sep . "\n";

        // Print rows
        foreach ($rows as $row) {
            $line = '|';
            foreach ($row as $i => $cell) {
                $line .= ' ' . str_pad((string) $cell, $colWidths[$i]) . ' |';
            }
            echo $line . "\n";
        }
    }

    protected function ask(string $question, ?string $default = null): string
    {
        $defaultText = $default ? " [{$default}]" : '';
        echo "{$question}{$defaultText}: ";

        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));

        return $line ?: ($default ?? '');
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? '[Y/n]' : '[y/N]';
        echo "{$question} {$defaultText}: ";

        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));

        if (empty($line)) {
            return $default;
        }

        return strtolower($line) === 'y' || strtolower($line) === 'yes';
    }

    protected function createDirectory(string $path): bool
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        return true;
    }
}
