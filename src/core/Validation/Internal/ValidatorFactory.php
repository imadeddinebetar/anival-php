<?php

namespace Core\Validation\Internal;

use Core\Validation\Contracts\ValidatorFactoryInterface;
use Core\Validation\Contracts\ValidatorInterface;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class ValidatorFactory implements ValidatorFactoryInterface
{
    public function make(array $data): ValidatorInterface
    {
        return new Validator($data);
    }
}
