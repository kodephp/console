<?php

declare(strict_types=1);

namespace Nova\Console\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nova\Console\Kernel;

class KernelTest extends TestCase
{
    public function testKernelCreation(): void
    {
        $kernel = new Kernel();
        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    public function testCommandRegistration(): void
    {
        $kernel = new Kernel();
        $kernel->add(TestCommand::class);
        
        $commands = $kernel->all();
        $this->assertArrayHasKey('test', $commands);
        $this->assertInstanceOf(TestCommand::class, $commands['test']);
    }
}