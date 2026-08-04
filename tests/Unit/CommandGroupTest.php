<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\CommandGroup;
use Kode\Console\Tests\Fixture\AttributeCommand;
use Kode\Console\Tests\Fixture\GreetCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommandGroup::class)]
final class CommandGroupTest extends TestCase
{
    public function testAddCommandSetsGroupAndTracksCount(): void
    {
        $cmd = new GreetCommand();
        $group = new CommandGroup('db', '数据库命令');
        $group->addCommand($cmd);

        self::assertSame('db', $group->getName());
        self::assertSame('数据库命令', $group->getDescription());
        self::assertSame('db', $cmd->getGroup());
        self::assertCount(1, $group->getCommands());
        self::assertSame(1, $group->count());
        self::assertTrue($group->has('greet'));
        self::assertFalse($group->has('missing'));
    }

    public function testIteratorAndAddCommands(): void
    {
        $group = new CommandGroup('net', '网络');
        $group->addCommands([new GreetCommand(), new AttributeCommand()]);

        $names = [];
        foreach ($group as $name => $command) {
            $names[] = $name;
        }

        self::assertSame(['greet', 'attr:ping'], $names);
        self::assertSame(2, $group->count());
    }
}
