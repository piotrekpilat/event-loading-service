<?php
declare(strict_types=1);

namespace App\Tests;

use App\Loader\EventSourceInterface;
use App\Loader\EventStorageInterface;
use App\Loader\SourceStateManagerInterface;
use App\MessageHandler\ProcessSourceMessageHandler;
use App\Message\ProcessSourceMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;

class ProcessSourceMessageHandlerTest extends TestCase
{
    public static function casesProvider(): array
    {
        return [
            [new ErrorEventSource(), true, false, false],
            [new EmptyEventSource(), false, true, false],
            [new ValidEventSource(), false, true, true],
            [new DuplicateEventSource(), false, true, false],
        ];
    }

    #[DataProvider('casesProvider')]
    public function testProcessSource(EventSourceInterface $provider, bool $expectFailure, bool $expectSuccess, bool $expectSave): void {
        $storage = $this->createMock(EventStorageInterface::class);
        $stateManager = $this->createMock(SourceStateManagerInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $stateManager->method('acquireLock')->willReturn(true);
        $stateManager->method('isAllowed')->willReturn(true);
        
        $storage->expects($expectSave ? $this->once() : $this->never())->method('saveEvents');
        $stateManager->expects($expectSuccess ? $this->once() : $this->never())->method('recordSuccess');
        $stateManager->expects($expectFailure ? $this->once() : $this->never())->method('recordFailure');
        $stateManager->expects($expectSave ? $this->once() : $this->never())->method('saveLastEventId');

        $bus->method('dispatch')->willReturnCallback(fn($msg) => new Envelope($msg));

        $handler = new ProcessSourceMessageHandler([$provider], $storage, $stateManager, $this->createMock(LoggerInterface::class), $bus);
        $handler(new ProcessSourceMessage($provider->getName()));
    }
}