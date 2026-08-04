<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 构造函数含必填参数的命令，用于验证 Reflector 对不可实例化命令的拦截
 */
final class StrictCommand extends Command
{
    public function __construct(string $required)
    {
        parent::__construct('strict', '需要必填参数: ' . $required);
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        return $this->ok();
    }
}
