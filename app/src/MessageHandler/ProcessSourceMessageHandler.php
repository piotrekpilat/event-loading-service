<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Loader\EventSourceInterface;
use App\Loader\EventStorageInterface;
use App\Loader\SourceStateManagerInterface;
use App\Message\ProcessSourceMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class ProcessSourceMessageHandler
{
    /** @var array<string, EventSourceInterface> */
    private array $sources = [];

    public function __construct(
        #[TaggedIterator('app.event_source')] iterable $sources,
        private EventStorageInterface $storage,
        private SourceStateManagerInterface $stateManager,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus
    ) {
        foreach ($sources as $source) {
            $this->sources[$source->getName()] = $source;
        }
    }

    public function __invoke(ProcessSourceMessage $message): void
    {
        $sourceName = $message->getSourceName();
        $source = $this->sources[$sourceName] ?? null;

        if (!$source) {
            $this->logger->error("Source {$sourceName} not found.");
            return;
        }

        try {
            $this->logger->info(sprintf("Processing source %s", $sourceName));
            $this->processSource($source, $sourceName);
        } finally {
            // TODO: maybe change to normal cron later?? 
            $this->messageBus->dispatch(
                new ProcessSourceMessage($sourceName),
                [new DelayStamp(100)] 
            );
        }
    }

    private function processSource(EventSourceInterface $source, string $sourceName): void
    {
        if (!$this->stateManager->acquireLock($sourceName, 30)) {
            return;
        }

        try {
            if ($this->stateManager->isCircuitBreakerOpen($sourceName)) {
                return;
            }

            if (!$this->stateManager->isAllowed($sourceName, 200)) {
                return;
            }

            $lastEventId = $this->stateManager->getLastEventId($sourceName);

            try {
                $events = $source->getEvents($lastEventId, 250);
                $this->stateManager->recordSuccess($sourceName);
            } catch (\Throwable $e) {
                $this->stateManager->recordFailure($sourceName);
                $this->logger->error("Source {$sourceName} failed!!! msg: " . $e->getMessage(), ['exception' => $e]);

                return;
            }

            if (empty($events)) {
                return;
            }

            $maxId = $lastEventId;
            $validEvents = [];
            $seenIds = [];

            foreach ($events as $event) {
                $eId = $event->getId();

                if ($eId <= $lastEventId || isset($seenIds[$eId])) {
                    // skip duplicates
                    continue;
                }

                $seenIds[$eId] = true;
                if ($eId > $maxId) {
                    $maxId = $eId;
                }
                $validEvents[] = $event;
            }

            if (!empty($validEvents)) {
                $this->storage->saveEvents($validEvents);
                $eventIds = array_map(function($e) { return $e->getId(); }, $validEvents);
                $this->logger->info(sprintf("Saved %d events for source %s (IDs: %s)", count($validEvents), $sourceName, implode(', ', $eventIds)));
            }

            if ($maxId > $lastEventId) {
                $this->stateManager->saveLastEventId($sourceName, $maxId);
            }
        } finally {
            $this->stateManager->releaseLock($sourceName);
        }
    }
}
