<?php

namespace Core\Validation\Contracts;

interface ValidatorFactoryInterface
{
    public function make(array $data): ValidatorInterface;
}
