<?php

namespace Core\Database\Internal;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @internal
 */
abstract class Model extends EloquentModel
{
    // We can add framework-specific model logic here if needed.
    // For now, it serves as a bridge to decouple the App from Illuminate namespace.
}
