<?php

namespace Core\Validation\Contracts;

interface ValidatorInterface
{
    /** @param array<string, string> $messages */
    public function setCustomMessages(array $messages): void;
    /** @param array<string, string> $rules */
    public function validate(array $rules, array $messages = []): bool;
    /** @return array<string, array<int, string>> */
    public function errors(): array;
    public function fails(): bool;
    public function validated(): array;
}
