<?php

declare(strict_types=1);

namespace App\Loader;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.event_source')]
interface EventSourceInterface
{
    public function getName(): string;
    public function getEvents(int $lastEventId, int $limit = 1000): array;
    public function getMaxEvents(): ?int;
}
