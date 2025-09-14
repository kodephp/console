<?php

declare(strict_types=1);

namespace Nova\Console;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ConsoleCommand
{
    public function __construct(
        public string $name,
        public string $desc = ''
    ) {}
}