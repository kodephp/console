<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 命令契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsCommand
{
    /**
     * 执行命令
     *
     * @return int 退出码，0 表示成功
     */
    public function fire(Input $in, Output $out): int;

    /**
     * 根据签名校验输入
     */
    public function validate(Input $in, Output $out): bool;

    /**
     * 输出命令帮助
     */
    public function showHelp(Input $in, Output $out): void;
}
