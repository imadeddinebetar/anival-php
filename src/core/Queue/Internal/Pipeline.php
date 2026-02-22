<?php

namespace Core\Queue\Internal;

use Core\Support\Pipeline as BasePipeline;

/**
 * Job middleware pipeline for the queue worker.
 *
 * Extends the generic pipeline — no additional behavior needed.
 *
 * @internal
 */
class Pipeline extends BasePipeline {}
