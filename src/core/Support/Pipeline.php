<?php

namespace Core\Support;

use Core\Container\Contracts\ContainerInterface;

/**
 * Generic pipeline for sending an object through a series of pipes.
 *
 * This base pipeline can be extended by domain-specific pipelines
 * (Http, Queue) that add their own middleware resolution behaviors.
 * @internal
 */
class Pipeline
{
    protected ContainerInterface $container;

    /** @var mixed */
    protected mixed $passable = null;

    /** @var array<int, string|object|callable> */
    protected array $pipes = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     */
    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param array<int, string|object|callable> $pipes
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(callable $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            function ($passable) use ($destination) {
                return $destination($passable);
            }
        );

        return $pipeline($this->passable);
    }

    /**
     * Get the pipe-carrying closure.
     */
    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container->get($pipe);
                }

                if (method_exists($pipe, 'handle')) {
                    return $pipe->handle($passable, $stack);
                }

                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                throw new \RuntimeException(
                    'Pipeline pipe must implement handle() method or be callable.'
                );
            };
        };
    }
}
