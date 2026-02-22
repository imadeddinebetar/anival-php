<?php

namespace Core\Log\Internal;

use Core\Container\Internal\Application;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Core\Log\Contracts\LogManagerInterface;

/**
 * @internal
 */
class LogManager implements LogManagerInterface
{
    /**
     * The application instance.
     *
     * @var \Core\Container\Internal\Application
     */
    protected $app;

    /**
     * The array of resolved channels.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Log manager instance.
     *
     * @param  \Core\Container\Internal\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create a new, on-demand aggregate logger instance.
     *
     * @param  array  $channels
     * @param  string|null  $channel
     * @return \Psr\Log\LoggerInterface
     */
    public function stack(array $channels, ?string $channel = null): LoggerInterface
    {
        return $this->createStackDriver(['channels' => $channels, 'channel' => $channel]);
    }

    /**
     * Get a log channel instance.
     *
     * @param  string|null  $channel
     * @return \Psr\Log\LoggerInterface
     */
    public function channel(?string $channel = null): LoggerInterface
    {
        return $this->driver($channel);
    }

    /**
     * Get a log driver instance.
     *
     * @param  string|null  $driver
     * @return \Psr\Log\LoggerInterface
     */
    public function driver($driver = null)
    {
        $name = $driver ?: $this->getDefaultDriver();

        return $this->channels[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given log instance.
     *
     * @param  string  $name
     * @return \Psr\Log\LoggerInterface
     */
    protected function resolve($name)
    {
        $config = $this->configurationFor($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Log message channel [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new \InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create a custom log driver instance.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createCustomDriver(array $config)
    {
        $factory = is_callable($via = $config['via']) ? $via : $this->app->make($via);

        return $factory($config);
    }

    /**
     * Create an aggregate log driver instance.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createStackDriver(array $config)
    {
        $handlers = [];

        foreach ($config['channels'] as $channel) {
            $logger = $this->channel($channel);
            if ($logger instanceof \Monolog\Logger) {
                $handlers = array_merge($handlers, $logger->getHandlers());
            }
        }

        if ($config['ignore_exceptions'] ?? false) {
            $handlers = array_map(function ($handler) {
                // Wrap handler to ignore exceptions if needed, 
                // but Monolog handlers don't have a standardized wrapper for this 
                // in the same way simple configuration implies.
                // For now, we will just use the handlers as is.
                return $handler;
            }, $handlers);
        }

        return new MonologLogger($this->parseChannel($config), $handlers);
    }

    /**
     * Create an instance of the single file log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createSingleDriver(array $config)
    {
        $this->ensureDirectoryExists($config['path']);

        return new MonologLogger($this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    $config['path'],
                    $this->level($config)
                ),
                $config
            ),
        ]);
    }

    /**
     * Create an instance of the daily file log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createDailyDriver(array $config)
    {
        $this->ensureDirectoryExists($config['path']);

        return new MonologLogger($this->parseChannel($config), [
            $this->prepareHandler(
                new RotatingFileHandler(
                    $config['path'],
                    $config['days'] ?? 7,
                    $this->level($config)
                ),
                $config
            ),
        ]);
    }

    /**
     * Create an instance of the syslog log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createSyslogDriver(array $config)
    {
        return new MonologLogger($this->parseChannel($config), [
            $this->prepareHandler(new SyslogHandler(
                $config['name'] ?? 'app',
                $config['facility'] ?? LOG_USER,
                $this->level($config)
            ), $config),
        ]);
    }

    /**
     * Create an instance of the "error" log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createErrorDriver(array $config)
    {
        $path = $config['path'] ?? storage_path('logs/error.log');
        $this->ensureDirectoryExists($path);

        return new MonologLogger($this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    $path,
                    $this->level($config)
                ),
                $config
            ),
        ]);
    }

    /**
     * Create an instance of the stdout log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createStdoutDriver(array $config)
    {
        return new MonologLogger($this->parseChannel($config), [
            $this->prepareHandler(
                new StreamHandler(
                    'php://stdout',
                    $this->level($config)
                ),
                $config
            ),
        ]);
    }

    /**
     * Ensure the directory for the log file exists.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureDirectoryExists($path)
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true); // @codeCoverageIgnore
        }
    }

    /**
     * Prepare the handler for usage.
     *
     * @param  \Monolog\Handler\HandlerInterface  $handler
     * @param  array  $config
     * @return \Monolog\Handler\HandlerInterface
     */
    protected function prepareHandler(HandlerInterface $handler, array $config = [])
    {
        // Here we could add formatters, processors, etc.
        return $handler;
    }

    /**
     * Get the log configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function configurationFor($name)
    {
        return \config("logging.channels.{$name}");
    }

    /**
     * Get the default log driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return \config('logging.default');
    }

    /**
     * Set the default log driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        \config(['logging.default' => $name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, \Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Parse the log level.
     *
     * @param  array  $config
     * @return int
     */
    protected function level(array $config)
    {
        $level = $config['level'] ?? 'debug';

        return match ($level) {
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
            default => MonologLogger::DEBUG,
        };
    }

    /**
     * Extract the log channel from the given configuration.
     *
     * @param  array  $config
     * @return string
     */
    protected function parseChannel(array $config)
    {
        return $config['name'] ?? $this->app->environment();
    }

    /**
     * The context array for all log messages.
     *
     * @var array
     */
    protected $context = [];

    /**
     * Add context to all future log messages.
     *
     * @param  array  $context
     * @return void
     */
    public function setContext(array $context): void
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Get the current context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Clear the current context.
     *
     * @return void
     */
    public function clearContext(): void
    {
        $this->context = [];
    }

    /**
     * Build the context for a log message.
     *
     * @param  array  $context
     * @return array
     */
    protected function buildContext(array $context = []): array
    {
        return array_merge($this->context, $context);
    }

    /**
     * System is unusable.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->emergency($message, $this->buildContext($context));
    }


    /**
     * Action must be taken immediately.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->alert($message, $this->buildContext($context));
    }

    /**
     * Critical conditions.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->critical($message, $this->buildContext($context));
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->error($message, $this->buildContext($context));
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->warning($message, $this->buildContext($context));
    }

    /**
     * Normal but significant events.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->notice($message, $this->buildContext($context));
    }

    /**
     * Interesting events.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->info($message, $this->buildContext($context));
    }

    /**
     * Detailed debug information.
     *
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->driver()->debug($message, $this->buildContext($context));
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->driver()->log($level, $message, $this->buildContext($context));
    }
}
