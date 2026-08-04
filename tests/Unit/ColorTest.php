<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
final class ColorTest extends TestCase
{
    public function testResolveReturnsSelfForEnum(): void
    {
        self::assertSame(Color::Red, Color::resolve(Color::Red));
    }

    public function testResolveNull(): void
    {
        self::assertNull(Color::resolve(null));
        self::assertNull(Color::resolve(''));
    }

    #[DataProvider('aliasProvider')]
    public function testResolveAcceptsNameAndAlias(string $input, ?Color $expected): void
    {
        self::assertSame($expected, Color::resolve($input));
    }

    /**
     * @return array<string, array{0: string, 1: ?Color}>
     */
    public static function aliasProvider(): array
    {
        return [
            'name' => ['red', Color::Red],
            'UPPER name' => ['RED', Color::Red],
            'purple alias' => ['purple', Color::Magenta],
            'bold_purple alias' => ['bold_purple', Color::BoldMagenta],
            'grey alias' => ['grey', Color::Gray],
            'comment alias' => ['comment', Color::Yellow],
            'question alias' => ['question', Color::Cyan],
            'note alias' => ['note', Color::Blue],
            'unknown returns null' => ['not-a-color', null],
        ];
    }

    public function testAnsiCodes(): void
    {
        self::assertSame('0;31', Color::Red->ansi());
        self::assertSame('1;31', Color::BoldRed->ansi());
        self::assertSame('1', Color::Bold->ansi());
    }

    public function testWrapAddsAnsiSequence(): void
    {
        self::assertSame("\033[0;31mhi\033[0m", Color::Red->wrap('hi'));
        self::assertSame("\033[1;36mxy\033[0m", Color::BoldCyan->wrap('xy'));
    }
}
