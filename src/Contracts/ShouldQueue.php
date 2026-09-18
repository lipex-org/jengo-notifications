<?php

declare(strict_types=1);

namespace Jengo\Notifications\Contracts;

interface ShouldQueue
{
    // Marker interface for notifications that should be queued.
    // Implementing classes can optionally specify:
    // public ?string $queue = null;
    // public ?int $delay = null;
    // public int $tries = 3;
    // public int $timeout = 60;
    // public array $backoff = [5, 15, 60];
}
