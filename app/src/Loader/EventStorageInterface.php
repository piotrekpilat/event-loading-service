<?php

declare(strict_types=1);

namespace App\Loader;

interface EventStorageInterface
{
    public function saveEvents(array $events): void;
}
