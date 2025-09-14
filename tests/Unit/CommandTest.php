<?php

declare(strict_types=1);

namespace Nova\Console\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class CommandTest extends TestCase
{
    public function testCommandCreation(): void
    {
        $command = new class extends Command {
            public function __construct()
            {
                $this->setName('test');
                $this->setDesc('Test command');
            }
            
            public function fire(Input $in, Output $out): int
            {
                return 0;
            }
        };

        $this->assertEquals('test', $command->name);
        $this->assertEquals('Test command', $command->desc);
    }
}