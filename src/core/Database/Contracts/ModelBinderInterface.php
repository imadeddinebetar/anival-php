<?php

namespace Core\Database\Contracts;

interface ModelBinderInterface
{
    /** @return mixed|null */
    public function bind(string $className, mixed $value): mixed;
}
