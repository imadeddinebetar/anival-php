<?php

namespace Core\Container\Contracts;

interface ServiceProviderInterface
{
    public function register(): void;
    public function boot(): void;
}
