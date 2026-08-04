<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 注解式测试命令
 */
#[AsCommand(
    name: 'attr:ping',
    description: '注解式命令',
    usage: 'attr:ping {host=localhost} {--times:int=1}',
    aliases: ['ping'],
    group: 'network',
    hidden: true,
)]
final class AttributeCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $out->line(sprintf('PING %s x%d', (string) $in->arg('host'), (int) $in->opt('times')));

        return $this->ok();
    }
}
