<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\Color;
use Kode\Console\Enum\Verbosity;
use Kode\Console\Output;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Output::class)]
final class OutputTest extends TestCase
{
    private function makeOutput(bool $decorated = false, Verbosity $verbosity = Verbosity::Normal): Output
    {
        return new Output(Streams::memory(), Streams::memory(), $decorated, $verbosity);
    }

    public function testWriteDoesNotAppendNewline(): void
    {
        $out = $this->makeOutput();
        $out->write('hi');

        self::assertSame('hi', Streams::read($out->getStream()));
    }

    public function testLineAppendsNewline(): void
    {
        $out = $this->makeOutput();
        $out->line('hi');

        self::assertSame('hi' . PHP_EOL, Streams::read($out->getStream()));
    }

    public function testFormatPlainWhenNotDecorated(): void
    {
        $out = $this->makeOutput(false);
        $out->write('hi', Color::Red);

        self::assertSame('hi', Streams::read($out->getStream()));
        self::assertFalse($out->isDecorated());
    }

    public function testFormatWrapsWhenDecorated(): void
    {
        $out = $this->makeOutput(true);
        $out->write('hi', Color::Red);

        self::assertSame("\033[0;31mhi\033[0m", Streams::read($out->getStream()));
        self::assertTrue($out->isDecorated());
    }

    public function testVerbosityGatesOutput(): void
    {
        $out = $this->makeOutput(false, Verbosity::Normal);
        $out->write('debug-only', null, Verbosity::Debug);

        self::assertSame('', Streams::read($out->getStream()));

        $out = $this->makeOutput(false, Verbosity::Debug);
        $out->write('debug-only', null, Verbosity::Debug);

        self::assertSame('debug-only', Streams::read($out->getStream()));
    }

    public function testSemanticHelpersRouting(): void
    {
        $out = $this->makeOutput();
        $out->info('i');
        $out->success('s');
        $out->comment('c');

        self::assertSame(
            'i' . PHP_EOL . 's' . PHP_EOL . 'c' . PHP_EOL,
            Streams::read($out->getStream()),
        );

        $out->error('e');
        $out->warn('w');

        self::assertSame(
            'e' . PHP_EOL . 'w' . PHP_EOL,
            Streams::read($out->getErrorStream()),
        );
    }

    public function testWarnSuppressedWhenQuiet(): void
    {
        $out = $this->makeOutput(false, Verbosity::Quiet);
        $out->warn('quiet-warn');

        self::assertSame('', Streams::read($out->getErrorStream()));
    }

    public function testJsonOutput(): void
    {
        $out = $this->makeOutput();
        $out->json(['a' => 1]);

        $content = Streams::read($out->getStream());
        self::assertStringContainsString('"a": 1', $content);
    }

    public function testTableAlignsAndRenders(): void
    {
        $out = $this->makeOutput();
        $out->table(['名称', '值'], [['张三', 10], ['李四', 200]]);

        $content = Streams::read($out->getStream());
        self::assertStringContainsString('名称', $content);
        self::assertStringContainsString('张三', $content);
        self::assertStringContainsString('李四', $content);
        // CJK 宽字符不应使整行错位导致值丢失
        self::assertStringContainsString('200', $content);
    }

    public function testProgressRendersPercent(): void
    {
        $out = $this->makeOutput();
        $out->progress(5, 10);

        self::assertStringContainsString('50%', Streams::read($out->getStream()));

        $out2 = $this->makeOutput();
        $out2->progress(1, 1);
        self::assertStringContainsString('100%', Streams::read($out2->getStream()));
    }

    public function testProgressSilentWhenQuiet(): void
    {
        $out = $this->makeOutput(false, Verbosity::Quiet);
        $out->progress(5, 10);

        self::assertSame('', Streams::read($out->getStream()));
    }

    public function testRawRespectsQuiet(): void
    {
        $out = $this->makeOutput();
        $out->raw('plain');
        self::assertSame('plain', Streams::read($out->getStream()));

        $quiet = $this->makeOutput(false, Verbosity::Quiet);
        $quiet->raw('plain');
        self::assertSame('', Streams::read($quiet->getStream()));
    }

    public function testNewLine(): void
    {
        $out = $this->makeOutput();
        $out->newLine(2);

        self::assertSame(PHP_EOL . PHP_EOL, Streams::read($out->getStream()));
    }

    public function testVerbosityAccessors(): void
    {
        $out = $this->makeOutput(false, Verbosity::Verbose);
        self::assertSame(Verbosity::Verbose, $out->getVerbosity());
        self::assertFalse($out->isQuiet());
        self::assertTrue($out->isVerbose());

        $out->setVerbosity(Verbosity::Quiet);
        self::assertTrue($out->isQuiet());
    }
}
