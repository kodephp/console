<?php

namespace Kode\Console\Contract;

interface IsEvent
{
    public function getName(): string;
    
    /**
     * @return array<string, mixed>
     */
    public function getData(): array;
}