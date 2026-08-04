<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Command;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Enum\Verbosity;
use Kode\Console\Exception\InvalidCommandException;
use Kode\Console\Input;
use Kode\Console\Output;
use Kode\Console\Tests\Fixture\AttributeCommand;
use Kode\Console\Tests\Fixture\GreetCommand;
use Kode\Console\Tests\Fixture\NamelessCommand;
use Kode\Console\Tests\Fixture\RequireValueCommand;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GreetCommand::class)]
#[CoversClass(AttributeCommand::class)]
#[CoversClass(Command::class)]
final class CommandTest extends TestCase
{
    private function makeOutput(): Output
    {
        return new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal);
    }

    public function testConstructorMetadata(): void
    {
        $cmd = new GreetCommand();

        self::assertSame('greet', $cmd->name);
        self::assertSame('问候某人', $cmd->desc);
        self::assertSame('greet {name : 姓名} {times:int=1} {--upper|-u:bool} {--suffix=!}', $cmd->usage);
        self::assertSame(['hi'], $cmd->getAliases());
        self::assertSame('demo', $cmd->getGroup());
        self::assertFalse($cmd->isHidden());
    }

    public function testAttributeMetadata(): void
    {
        $cmd = new AttributeCommand();

        self::assertSame('attr:ping', $cmd->name);
        self::assertSame(['ping'], $cmd->getAliases());
        self::assertSame('network', $cmd->getGroup());
        self::assertTrue($cmd->isHidden());
    }

    public function testNamelessCommandThrows(): void
    {
        $this->expectException(InvalidCommandException::class);

        new NamelessCommand();
    }

    public function testValidateFailsForMissingRequiredArgument(): void
    {
        $cmd = new GreetCommand();
        $in = new Input(['greet'], $cmd->getSignature());
        $out = $this->makeOutput();

        self::assertFalse($cmd->validate($in, $out));
        self::assertStringContainsString('缺少必填参数', Streams::read($out->getErrorStream()));
    }

    public function testValidatePassesWhenRequiredProvided(): void
    {
        $cmd = new GreetCommand();
        $in = new Input(['greet', 'World'], $cmd->getSignature());
        $out = $this->makeOutput();

        self::assertTrue($cmd->validate($in, $out));
    }

    public function testValidateValueRequiredOption(): void
    {
        $cmd = new RequireValueCommand();

        // 提供了选项却未带值 → 失败
        $missing = new Input(['rv', '--dsn'], $cmd->getSignature());
        self::assertFalse($cmd->validate($missing, $this->makeOutput()));

        // 提供了选项且带值 → 通过
        $withValue = new Input(['rv', '--dsn=foo'], $cmd->getSignature());
        self::assertTrue($cmd->validate($withValue, $this->makeOutput()));

        // 未提供该选项 → 通过（非必填）
        $omitted = new Input(['rv'], $cmd->getSignature());
        self::assertTrue($cmd->validate($omitted, $this->makeOutput()));
    }

    public function testShowHelpRendersSections(): void
    {
        $cmd = new GreetCommand();
        $in = new Input(['greet'], $cmd->getSignature());
        $out = $this->makeOutput();

        $cmd->showHelp($in, $out);

        $content = Streams::read($out->getStream());
        self::assertStringContainsString('greet', $content);
        self::assertStringContainsString('用法:', $content);
        self::assertStringContainsString('参数:', $content);
        self::assertStringContainsString('选项:', $content);
    }

    public function testOkReturnsZero(): void
    {
        $cmd = new GreetCommand();

        self::assertSame(0, $cmd->ok());
    }

    public function testFailReturnsNormalizedCode(): void
    {
        $cmd = new GreetCommand();
        $out = $this->makeOutput();

        self::assertSame(1, $cmd->fail($out, 'boom'));
        self::assertSame(2, $cmd->fail($out, 'boom', ExitCode::InvalidInput));
        self::assertStringContainsString('boom', Streams::read($out->getErrorStream()));
    }
}
