<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Exception\InvalidCommandException;
use Kode\Console\Helper\Reflector;
use Kode\Console\Tests\Fixture\AttributeCommand;
use Kode\Console\Tests\Fixture\GreetCommand;
use Kode\Console\Tests\Fixture\StrictCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reflector::class)]
final class ReflectorTest extends TestCase
{
    public function testCommandMetaForAttribute(): void
    {
        $meta = Reflector::commandMeta(AttributeCommand::class);

        self::assertInstanceOf(AsCommand::class, $meta);
        self::assertSame('attr:ping', $meta->name);
        self::assertSame(['ping'], $meta->aliases);
    }

    public function testCommandMetaNullWithoutAttribute(): void
    {
        self::assertNull(Reflector::commandMeta(GreetCommand::class));
    }

    public function testOfThrowsForUnknownClass(): void
    {
        $this->expectException(InvalidCommandException::class);

        // @phpstan-ignore argument.type, argument.templateType
        Reflector::of('Kode\Console\Tests\Fixture\DoesNotExist');
    }

    public function testOfThrowsForNonCommand(): void
    {
        $this->expectException(InvalidCommandException::class);

        // @phpstan-ignore argument.type, argument.templateType
        Reflector::of(\stdClass::class);
    }

    public function testInstantiate(): void
    {
        self::assertInstanceOf(Command::class, Reflector::instantiate(GreetCommand::class));
        self::assertInstanceOf(Command::class, Reflector::instantiate(AttributeCommand::class));
    }

    public function testInstantiateThrowsForRequiredCtorParam(): void
    {
        $this->expectException(InvalidCommandException::class);

        Reflector::instantiate(StrictCommand::class);
    }

    public function testIsInstantiable(): void
    {
        self::assertTrue(Reflector::isInstantiable(GreetCommand::class));
        self::assertFalse(Reflector::isInstantiable('Kode\Console\Tests\Fixture\DoesNotExist'));
    }

    public function testFlushDoesNotBreakSubsequentReads(): void
    {
        Reflector::flush();

        $meta = Reflector::commandMeta(AttributeCommand::class);
        self::assertInstanceOf(AsCommand::class, $meta);
        self::assertSame('attr:ping', $meta->name);
    }
}
