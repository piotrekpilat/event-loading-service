<?php

declare(strict_types=1);

namespace App\Loader;

use Psr\Log\LoggerInterface;

class DatabaseEventStorage implements EventStorageInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function saveEvents(array $events): void
    {
        if (empty($events)) {
            return;
        }

        $this->logger->info(sprintf(
            "Stored %d events from %s (Max ID: %d)",
            count($events),
            $events[0]->getData()['source'] ?? 'unknown',
            end($events)->getId()
        ), ['events' => array_map(fn($event) => ['id' => $event->getId(), 'data' => $event->getData()], $events)]);
    }
}
