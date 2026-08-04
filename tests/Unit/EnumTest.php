<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\ArgType;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Enum\Verbosity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArgType::class)]
#[CoversClass(ExitCode::class)]
#[CoversClass(Verbosity::class)]
final class EnumTest extends TestCase
{
    // ------------------------------------------------------------------
    // ArgType::parse
    // ------------------------------------------------------------------

    #[DataProvider('parseProvider')]
    public function testParse(string $name, ArgType $expected): void
    {
        self::assertSame($expected, ArgType::parse($name));
    }

    /**
     * @return array<string, array{0: string, 1: ArgType}>
     */
    public static function parseProvider(): array
    {
        return [
            'int' => ['int', ArgType::Int],
            'integer alias' => ['integer', ArgType::Int],
            'float' => ['float', ArgType::Float],
            'double alias' => ['double', ArgType::Float],
            'number alias' => ['number', ArgType::Float],
            'bool' => ['bool', ArgType::Bool],
            'boolean alias' => ['boolean', ArgType::Bool],
            'array' => ['array', ArgType::Array],
            'csv alias' => ['csv', ArgType::Array],
            'json' => ['json', ArgType::Json],
            'unknown falls back to string' => ['whatever', ArgType::String],
        ];
    }

    // ------------------------------------------------------------------
    // ArgType::cast
    // ------------------------------------------------------------------

    #[DataProvider('castProvider')]
    public function testCast(ArgType $type, mixed $value, mixed $expected): void
    {
        self::assertEquals($expected, $type->cast($value));
    }

    /**
     * @return array<string, array{0: ArgType, 1: mixed, 2: mixed}>
     */
    public static function castProvider(): array
    {
        return [
            'string scalar' => [ArgType::String, 42, '42'],
            'string bool' => [ArgType::String, true, '1'],
            'string array' => [ArgType::String, ['a', 'b'], 'a,b'],
            'int from string' => [ArgType::Int, '42', 42],
            'int from float string' => [ArgType::Int, '3.9', 3],
            'int from garbage' => [ArgType::Int, 'abc', 0],
            'int from bool' => [ArgType::Int, true, 1],
            'float' => [ArgType::Float, '3.14', 3.14],
            'bool yes' => [ArgType::Bool, 'yes', true],
            'bool no' => [ArgType::Bool, 'no', false],
            'bool 1' => [ArgType::Bool, '1', true],
            'bool 0' => [ArgType::Bool, '0', false],
            'array csv' => [ArgType::Array, 'a, b ,c', ['a', 'b', 'c']],
            'array empty' => [ArgType::Array, '', []],
            'json object' => [ArgType::Json, '{"a":1}', ['a' => 1]],
            'json invalid' => [ArgType::Json, '{oops', null],
            'json empty' => [ArgType::Json, '', null],
            'null passthrough int' => [ArgType::Int, null, null],
            'null passthrough json' => [ArgType::Json, null, null],
        ];
    }

    public function testIsDefaultType(): void
    {
        self::assertTrue(ArgType::String->isDefault());
        self::assertFalse(ArgType::Int->isDefault());
        self::assertFalse(ArgType::Bool->isDefault());
    }

    // ------------------------------------------------------------------
    // ExitCode
    // ------------------------------------------------------------------

    public function testExitCodeIsSuccess(): void
    {
        self::assertTrue(ExitCode::Success->isSuccess());
        self::assertFalse(ExitCode::Failure->isSuccess());
        self::assertFalse(ExitCode::NotFound->isSuccess());
    }

    public function testExitCodeNormalize(): void
    {
        self::assertSame(0, ExitCode::normalize(ExitCode::Success));
        self::assertSame(127, ExitCode::normalize(ExitCode::NotFound));
        self::assertSame(5, ExitCode::normalize(5));
    }

    // ------------------------------------------------------------------
    // Verbosity
    // ------------------------------------------------------------------

    public function testVerbosityAllows(): void
    {
        self::assertFalse(Verbosity::Quiet->allows(Verbosity::Normal));
        self::assertTrue(Verbosity::Normal->allows(Verbosity::Normal));
        self::assertFalse(Verbosity::Normal->allows(Verbosity::Verbose));
        self::assertTrue(Verbosity::Verbose->allows(Verbosity::Normal));
        self::assertTrue(Verbosity::Debug->allows(Verbosity::Verbose));
    }
}
