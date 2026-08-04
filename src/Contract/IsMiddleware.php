<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 中间件契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsMiddleware
{
    /**
     * 处理请求并调用下一个环节
     *
     * @param callable(Input, Output): int $next
     */
    public function handle(Input $input, Output $output, callable $next): int;
}
