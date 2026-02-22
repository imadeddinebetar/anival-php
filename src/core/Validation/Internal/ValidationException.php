<?php

namespace Core\Validation\Internal;

/**
 * @internal
 */
class ValidationException extends \Exception
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('The given data was invalid.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
