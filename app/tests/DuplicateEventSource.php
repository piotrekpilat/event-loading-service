<?php

declare(strict_types=1);

namespace App\Tests;

use App\Loader\EventSourceInterface;

class DuplicateEventSource implements EventSourceInterface
{
    public function getName(): string
    {
        return 'test_provider';
    }
    public function getEvents(int $lastEventId, int $limit = 1000): array
    {
        $e1 = new \App\Loader\Event(0); // assuming lastEventId is 0 or higher
        return [$e1];
    }
}
