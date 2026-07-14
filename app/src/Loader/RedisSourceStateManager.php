<?php

declare(strict_types=1);

namespace App\Loader;

class RedisSourceStateManager implements SourceStateManagerInterface
{
    private const PREFIX_STATE = 'cb:state:';
    private const PREFIX_FAILURES = 'cb:failures:';
    private const STATE_OPEN = 'open';

    public function __construct(
        private \Redis $redis,
        private int $failureThreshold = 5,
        private int $retryTimeout = 60
    ) {
    }

    public function acquireLock(string $sourceName, int $ttlSeconds): bool
    {
        return (bool) $this->redis->set("source_lock:{$sourceName}", 1, ['NX', 'EX' => $ttlSeconds]);
    }

    public function releaseLock(string $sourceName): void
    {
        $this->redis->delete("source_lock:{$sourceName}");
    }

    public function isAllowed(string $sourceName, int $intervalMilliseconds): bool
    {
        return (bool) $this->redis->set("source_ratelimit:{$sourceName}", 1, ['NX', 'PX' => $intervalMilliseconds]);
    }

    public function isCircuitBreakerOpen(string $sourceName): bool
    {
        $stateKey = self::PREFIX_STATE . $sourceName;
        return $this->redis->get($stateKey) === self::STATE_OPEN;
    }

    public function recordSuccess(string $sourceName): void
    {

        $stateKey = self::PREFIX_STATE . $sourceName;
        $this->redis->delete(self::PREFIX_FAILURES . $sourceName, $stateKey);
    }

    public function recordFailure(string $sourceName): void
    {
        $failuresKey = self::PREFIX_FAILURES . $sourceName;
        $stateKey = self::PREFIX_STATE . $sourceName;

        $failures = (int) $this->redis->incr($failuresKey);
        if ($failures === 1) {
            $this->redis->expire($failuresKey, $this->retryTimeout);
        }

        if ($failures >= $this->failureThreshold) {
            // open circuit breaker
            $this->redis->set($stateKey, self::STATE_OPEN, ['EX' => $this->retryTimeout]);
            $this->redis->delete($failuresKey);
        }
    }

    public function getLastEventId(string $sourceName): int
    {
        return (int) $this->redis->get("source_lastid:{$sourceName}");
    }

    public function saveLastEventId(string $sourceName, int $eventId): void
    {
        $this->redis->set("source_lastid:{$sourceName}", $eventId);
    }
}
