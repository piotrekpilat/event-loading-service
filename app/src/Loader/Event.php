<?php

declare(strict_types=1);

namespace App\Loader;

class Event
{
    public function __construct(
        private readonly int $id,
        private readonly array $data = []
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
