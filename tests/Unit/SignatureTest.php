<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Definition\Argument;
use Kode\Console\Definition\Option;
use Kode\Console\Enum\ArgType;
use Kode\Console\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Signature::class)]
#[UsesClass(Argument::class)]
#[UsesClass(Option::class)]
#[UsesClass(ArgType::class)]
final class SignatureTest extends TestCase
{
    public function testParsesCommandName(): void
    {
        self::assertSame('app:serve', (new Signature('app:serve {root?}'))->getName());
        self::assertSame('bare', (new Signature('bare'))->getName());
    }

    public function testParsesRequiredAndOptionalArguments(): void
    {
        $signature = new Signature('build {source} {target?}');

        $source = $signature->getArgument('source');
        $target = $signature->getArgument('target');

        self::assertInstanceOf(Argument::class, $source);
        self::assertInstanceOf(Argument::class, $target);
        self::assertTrue($source->required);
        self::assertFalse($target->required);
        self::assertSame(['source', 'target'], $signature->argumentNames());
    }

    public function testParsesTypedArgumentWithDefault(): void
    {
        $argument = (new Signature('run {times:int=3}'))->getArgument('times');

        self::assertInstanceOf(Argument::class, $argument);
        self::assertSame(ArgType::Int, $argument->type);
        self::assertFalse($argument->required);
        self::assertSame(3, $argument->default);
    }

    public function testParsesVariadicArgument(): void
    {
        $argument = (new Signature('cat {files*}'))->getArgument('files');

        self::assertInstanceOf(Argument::class, $argument);
        self::assertTrue($argument->variadic);
        self::assertSame('files...', $argument->label());
    }

    public function testParsesOptionWithShortcutTypeAndDefault(): void
    {
        $signature = new Signature('serve {--port|-p:int=8080}');
        $option = $signature->getOption('port');

        self::assertInstanceOf(Option::class, $option);
        self::assertSame('p', $option->shortcut);
        self::assertSame(ArgType::Int, $option->type);
        self::assertTrue($option->acceptsValue);
        self::assertFalse($option->valueRequired);
        self::assertSame(8080, $option->default);
        self::assertSame($option, $signature->optionForShortcut('p'));
    }

    public function testShortcutMayBeDeclaredFirst(): void
    {
        $option = (new Signature('serve {-p|--port=80}'))->getOption('port');

        self::assertInstanceOf(Option::class, $option);
        self::assertSame('p', $option->shortcut);
    }

    public function testBooleanOptionDoesNotAcceptValue(): void
    {
        $option = (new Signature('serve {--secure:bool}'))->getOption('secure');

        self::assertInstanceOf(Option::class, $option);
        self::assertFalse($option->acceptsValue);
    }

    public function testEmptyDefaultMarksValueAsRequired(): void
    {
        $option = (new Signature('db {--dsn=}'))->getOption('dsn');

        self::assertInstanceOf(Option::class, $option);
        self::assertTrue($option->valueRequired);
        self::assertNull($option->default);
        self::assertSame('    --dsn=DSN', $option->label());
    }

    public function testParsesDescriptionSeparatedBySpacedColon(): void
    {
        $signature = new Signature('greet {name : 姓名} {--upper:bool : 全部大写}');

        $argument = $signature->getArgument('name');
        $option = $signature->getOption('upper');

        self::assertInstanceOf(Argument::class, $argument);
        self::assertInstanceOf(Option::class, $option);
        self::assertSame('姓名', $argument->description);
        self::assertSame('全部大写', $option->description);
    }

    public function testUnknownTypeFallsBackToString(): void
    {
        $argument = (new Signature('x {a:whatever}'))->getArgument('a');

        self::assertInstanceOf(Argument::class, $argument);
        self::assertSame(ArgType::String, $argument->type);
    }
}
