<?php

declare(strict_types=1);

namespace App\Message;

class ProcessSourceMessage
{
    public function __construct(
        private string $sourceName
    ) {
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }
}
