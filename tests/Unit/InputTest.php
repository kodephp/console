<?php

namespace Kode\Console\Tests\Unit;

use Kode\Console\Input;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function testInputParsing(): void
    {
        $argv = ['script.php', 'command', 'arg1', '--option=value', '--flag', '-v'];
        $input = new Input($argv);

        $this->assertEquals(['script.php', 'command', 'arg1', '--option=value', '--flag', '-v'], $input->raw());
    }

    public function testInputArg(): void
    {
        $argv = ['script.php', 'command', 'arg1', 'arg2'];
        $input = new Input($argv);

        // The first argument is at index 0, which should be 'command'
        $this->assertEquals('command', $input->arg(0));
        $this->assertEquals('arg1', $input->arg(1));
        $this->assertEquals('arg2', $input->arg(2));
    }

    public function testInputOpt(): void
    {
        $argv = ['script.php', 'command', '--port=8080'];
        $input = new Input($argv);

        $this->assertEquals('8080', $input->opt('port'));
    }

    public function testInputFlag(): void
    {
        $argv = ['script.php', 'command', '--verbose', '-v'];
        $input = new Input($argv);

        $this->assertTrue($input->flag('verbose'));
        $this->assertTrue($input->flag('v'));
        $this->assertFalse($input->flag('nonexistent'));
    }
}