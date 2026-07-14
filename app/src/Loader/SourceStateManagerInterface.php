<?php

declare(strict_types=1);

namespace App\Loader;

interface SourceStateManagerInterface
{
    public function acquireLock(string $sourceName, int $ttlSeconds): bool;
    public function releaseLock(string $sourceName): void;

    public function isAllowed(string $sourceName, int $intervalMilliseconds): bool;

    public function isCircuitBreakerOpen(string $sourceName): bool;
    public function recordSuccess(string $sourceName): void;
    public function recordFailure(string $sourceName): void;

    public function getLastEventId(string $sourceName): int;
    public function saveLastEventId(string $sourceName, int $eventId): void;
}
