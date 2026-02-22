<?php

namespace Core\Database\Internal;

use Core\Database\Contracts\ModelBinderInterface;

/**
 * @internal
 */
class ModelBinder implements ModelBinderInterface
{
    public function bind(string $className, mixed $value): mixed
    {
        if (is_subclass_of($className, Model::class)) {
            return $className::findOrFail($value);
        }
        return null;
    }
}
