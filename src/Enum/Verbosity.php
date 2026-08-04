<?php

declare(strict_types=1);

namespace Kode\Console\Enum;

/**
 * 输出详细度
 *
 * 由内核根据全局标志自动设置：
 * - `-q` / `--quiet`   => Quiet
 * - 默认                => Normal
 * - `-v` / `--verbose` => Verbose
 * - `-vvv` / `--debug` => Debug
 *
 * @package Kode\Console
 * @since 4.0.0
 */
enum Verbosity: int
{
    case Quiet = 0;
    case Normal = 1;
    case Verbose = 2;
    case Debug = 3;

    /**
     * 当前详细度是否允许输出 `$level` 级别的内容
     */
    public function allows(self $level): bool
    {
        return $this->value >= $level->value;
    }
}
