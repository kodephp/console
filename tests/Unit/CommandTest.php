<?php

namespace Kode\Console\Tests\Unit;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testCommandCreation(): void
    {
        $command = new class ('test', 'Test command') extends Command {
            
            public function fire(Input $in, Output $out): int
            {
                return 0;
            }
        };

        $this->assertEquals('test', $command->name);
        $this->assertEquals('Test command', $command->desc);
    }
}