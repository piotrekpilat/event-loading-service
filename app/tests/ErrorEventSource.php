<?php

declare(strict_types=1);

namespace App\Tests;

use App\Loader\EventSourceInterface;

class ErrorEventSource implements EventSourceInterface
{
    public function getName(): string
    {
        return 'test_provider';
    }
    public function getEvents(int $lastEventId, int $limit = 1000): array
    {
        throw new \Exception('Simulated provider error');
    }
}
