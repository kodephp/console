<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;
use RuntimeException;

/**
 * always throws：用于验证内核异常兜底
 */
#[AsCommand(name: 'boom', description: '抛出异常')]
final class ExplodingCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        throw new RuntimeException('炸了');
    }
}
