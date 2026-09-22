<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 名字正好叫 list 的测试命令（用于验证不被内置帮助词永久遮蔽）
 */
final class ListCommand extends Command
{
    public function __construct()
    {
        parent::__construct('list', '业务自己的 list 命令');

        $this->group('demo');
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $out->line('LIST-RAN');

        return $this->ok();
    }
}
