<?php

namespace Kode\Console;

#[\Attribute]
class ConsoleCommand
{
    public function __construct(
        public string $name,
        public string $desc = ''
    ) {}
}