<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\Verbosity;
use Kode\Console\Exception\CommandNotFoundException;
use Kode\Console\Kernel;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Output;
use Kode\Console\Tests\Fixture\AttributeCommand;
use Kode\Console\Tests\Fixture\ExplodingCommand;
use Kode\Console\Tests\Fixture\GreetCommand;
use Kode\Console\Tests\Fixture\SpyMiddleware;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Kernel::class)]
#[CoversClass(GreetCommand::class)]
#[CoversClass(AttributeCommand::class)]
final class KernelTest extends TestCase
{
    private function makeOutput(): Output
    {
        return new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal);
    }

    private function kernel(): Kernel
    {
        return (new Kernel())->setOutput($this->makeOutput());
    }

    public function testRegisterAndFindWithAlias(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        self::assertInstanceOf(GreetCommand::class, $kernel->find('greet'));
        self::assertInstanceOf(GreetCommand::class, $kernel->find('hi'));
        self::assertTrue($kernel->has('greet'));
        self::assertFalse($kernel->has('nope'));
        self::assertArrayHasKey('greet', $kernel->all());
    }

    public function testResolveThrowsWithSuggestions(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        $this->expectException(CommandNotFoundException::class);

        try {
            $kernel->resolve('greetx');
        } catch (CommandNotFoundException $e) {
            self::assertContains('greet', $e->suggestions());
            throw $e;
        }
    }

    public function testBootListsCommandsAndHidesHidden(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class)->add(AttributeCommand::class);
        $code = $kernel->boot(['console']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('Kode Console 4.0.0', $content);
        self::assertStringContainsString('greet', $content);
        self::assertStringNotContainsString('attr:ping', $content);
    }

    public function testBootVersion(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', '--version']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('4.0.0', $content);
    }

    public function testBootHelpForCommand(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'help', 'greet']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('用法:', $content);
        self::assertStringContainsString('greet', $content);
    }

    public function testBootRunsCommand(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greet', 'World']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('Hello World!', $content);
    }

    public function testBootMissingArgumentReturnsInvalidInput(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greet']);

        self::assertSame(2, $code);
        self::assertStringContainsString('缺少必填参数', Streams::read($kernel->getOutput()->getErrorStream()));
    }

    public function testBootUnknownCommandReturnsNotFound(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greetx']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(127, $code);
        self::assertStringContainsString('你是不是想执行', $content);
        self::assertStringContainsString('greet', $content);
    }

    public function testExceptionPropagatesWhenNotCaught(): void
    {
        $kernel = (new Kernel())
            ->catchExceptions(false)
            ->setOutput($this->makeOutput())
            ->add(ExplodingCommand::class);

        $this->expectException(RuntimeException::class);

        $kernel->boot(['console', 'boom']);
    }

    public function testMiddlewareOrdering(): void
    {
        $kernel = $this->kernel()
            ->add(GreetCommand::class)
            ->addMiddleware(new SpyMiddleware('A'))
            ->addMiddleware(new SpyMiddleware('B'));

        $kernel->boot(['console', 'greet', 'World']);

        $content = Streams::read($kernel->getOutput()->getStream());
        $posA = strpos($content, '[MW:A]');
        $posB = strpos($content, '[MW:B]');
        $posHello = strpos($content, 'Hello World!');

        self::assertNotFalse($posA);
        self::assertNotFalse($posB);
        self::assertNotFalse($posHello);
        self::assertLessThan($posB, $posA);
        self::assertLessThan($posHello, $posB);
    }

    public function testLoggingMiddlewareEmitsDebugOnlyAtDebugVerbosity(): void
    {
        $quiet = (new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal));
        $kernel = (new Kernel())->setOutput($quiet)->add(GreetCommand::class)->addMiddleware(new LoggingMiddleware());
        $kernel->boot(['console', 'greet', 'World']);
        self::assertStringNotContainsString('命令开始执行', Streams::read($quiet->getStream()));
        self::assertStringContainsString('Hello World!', Streams::read($quiet->getStream()));

        $debug = (new Output(Streams::memory(), Streams::memory(), false, Verbosity::Debug));
        $kernel2 = (new Kernel())->setOutput($debug)->add(GreetCommand::class)->addMiddleware(new LoggingMiddleware());
        $kernel2->boot(['console', 'greet', 'World']);
        self::assertStringContainsString('命令开始执行', Streams::read($debug->getStream()));
    }
}
