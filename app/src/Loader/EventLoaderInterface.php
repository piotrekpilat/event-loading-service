<?php

declare(strict_types=1);

namespace App\Loader;

interface EventLoaderInterface
{
    public function run(): void;
}
