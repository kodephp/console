<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 既没有构造参数也没有注解，实例化时应当抛出异常
 */
final class NamelessCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        return $this->ok();
    }
}
