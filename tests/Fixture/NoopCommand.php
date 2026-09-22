<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 不产生任何输出的测试命令（用于跨 boot 的输出状态断言）
 */
final class NoopCommand extends Command
{
    public function __construct()
    {
        parent::__construct('noop', '什么都不做');

        $this->group('demo');
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        return $this->ok();
    }
}
