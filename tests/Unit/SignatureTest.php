<?php

declare(strict_types=1);

namespace Nova\Console\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Nova\Console\Signature;

class SignatureTest extends TestCase
{
    public function testSignatureParsing(): void
    {
        $signature = new Signature('command {arg} {--option}');

        $this->assertIsArray($signature->getArguments());
        $this->assertIsArray($signature->getOptions());
    }

    public function testArgumentParsing(): void
    {
        $signature = new Signature('command {arg} {optional?} {default=value}');

        $arguments = $signature->getArguments();
        $this->assertCount(3, $arguments);
        $this->assertArrayHasKey('arg', $arguments);
        $this->assertArrayHasKey('optional', $arguments);
        $this->assertArrayHasKey('default', $arguments);
    }

    public function testOptionParsing(): void
    {
        $signature = new Signature('command {--flag} {--option=} {--default=value}');

        $options = $signature->getOptions();
        $this->assertCount(3, $options);
        $this->assertArrayHasKey('flag', $options);
        $this->assertArrayHasKey('option', $options);
        $this->assertArrayHasKey('default', $options);
        
        // Verify that the option values are correctly parsed
        $this->assertIsArray($options['flag']);
        $this->assertIsArray($options['option']);
        $this->assertIsArray($options['default']);
    }
}