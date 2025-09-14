<?php

declare(strict_types=1);

namespace Nova\Console\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nova\Console\Output;

class OutputTest extends TestCase
{
    public function testOutputMethods(): void
    {
        $output = new Output();

        // 这些方法应该可以正常调用而不会抛出异常
        $this->expectOutputRegex('/Test message/');
        $output->line('Test message');

        $this->expectOutputRegex('/Info message/');
        $output->info('Info message');

        $this->expectOutputRegex('/Warning message/');
        $output->warn('Warning message');

        $this->expectOutputRegex('/Error message/');
        $output->error('Error message');

        $this->expectOutputRegex('/Success message/');
        $output->success('Success message');
    }

    public function testRawOutput(): void
    {
        $output = new Output();

        $this->expectOutputString('Raw text');
        $output->raw('Raw text');
    }
}