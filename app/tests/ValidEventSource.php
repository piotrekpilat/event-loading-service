<?php

declare(strict_types=1);

namespace App\Tests;

use App\Loader\EventSourceInterface;

class ValidEventSource implements EventSourceInterface
{
    public function getName(): string
    {
        return 'test_provider';
    }
    public function getEvents(int $lastEventId, int $limit = 1000): array
    {
        $e1 = new \App\Loader\Event(10);
        $e2 = new \App\Loader\Event(15);
        return [$e1, $e2];
    }
}
