<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 含有「一旦使用就必须带值」选项 {--dsn=} 的命令，用于校验逻辑测试
 */
final class RequireValueCommand extends Command
{
    public function __construct()
    {
        parent::__construct('rv', '需要值的选项', 'rv {--dsn=}');
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $out->line('DSN=' . (string) $in->opt('dsn'));

        return $this->ok();
    }
}
