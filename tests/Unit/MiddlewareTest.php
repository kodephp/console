<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\Verbosity;
use Kode\Console\Event;
use Kode\Console\Input;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Output;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoggingMiddleware::class)]
#[CoversClass(CommandLogger::class)]
final class MiddlewareTest extends TestCase
{
    private function makeOutput(Verbosity $verbosity = Verbosity::Normal): Output
    {
        return new Output(Streams::memory(), Streams::memory(), false, $verbosity);
    }

    public function testLoggingMiddlewareReturnsNextCode(): void
    {
        $mw = new LoggingMiddleware();
        $out = $this->makeOutput();

        $code = $mw->handle(new Input(['x']), $out, static fn () => 7);

        self::assertSame(7, $code);
    }

    public function testLoggingMiddlewareDebugHiddenAtNormalVerbosity(): void
    {
        $mw = new LoggingMiddleware();
        $out = $this->makeOutput(Verbosity::Normal);

        $mw->handle(new Input(['x']), $out, static fn () => 0);

        self::assertSame('', Streams::read($out->getStream()));
    }

    public function testLoggingMiddlewareDebugVisibleAtDebugVerbosity(): void
    {
        $mw = new LoggingMiddleware();
        $out = $this->makeOutput(Verbosity::Debug);

        $mw->handle(new Input(['x']), $out, static fn () => 0);

        $content = Streams::read($out->getStream());
        self::assertStringContainsString('命令开始执行', $content);
        self::assertStringContainsString('命令执行完成', $content);
    }

    public function testCommandLoggerWritesJsonLine(): void
    {
        $file = sys_get_temp_dir() . '/kode_console_test_' . uniqid('', true) . '.log';

        try {
            $logger = new CommandLogger($file);
            $logger->handle(new Event('command.executed', ['code' => 0, 'command' => 'greet']));

            self::assertFileExists($file);
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            self::assertNotFalse($lines);
            self::assertCount(1, $lines);

            $decoded = json_decode((string) $lines[0], true);
            self::assertIsArray($decoded);
            self::assertSame('command.executed', $decoded['event']);
            self::assertSame('greet', $decoded['data']['command']);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
