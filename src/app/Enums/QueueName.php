<?php

namespace App\Enums;

enum QueueName: string
{
    case Default = 'default';
    case High = 'high';
    case Low = 'low';
}
