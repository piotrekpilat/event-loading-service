<?php

declare(strict_types=1);

namespace App\Loader;

class SloppyMockEventSource implements EventSourceInterface
{
    public function __construct(
        private readonly string $name,
        private readonly int $maxEvents
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEvents(int $lastEventId, int $limit = 1000): array
    {
        $start = $lastEventId + 1;
        $end = min($start + $limit - 1, $this->maxEvents);
        
        $events = [];
        if ($start <= $end) {
            for ($i = $start; $i <= $end; $i++) {
                $events[] = new Event($i, [
                    'id' => $i,
                    'source' => $this->name,
                    'timestamp' => time(),
                ]);
            }
        }
        
        return $events;
    }

    public function getMaxEvents(): ?int
    {
        return $this->maxEvents;
    }
}
