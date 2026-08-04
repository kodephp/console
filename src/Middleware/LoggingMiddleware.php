<?php

declare(strict_types=1);

namespace Kode\Console\Middleware;

use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 耗时统计中间件
 *
 * 使用 `hrtime()` 获取单调时钟，不受系统时间调整影响；
 * 输出仅在 `-v` 及以上详细度下可见，正常执行时不会污染标准输出。
 *
 * @package Kode\Console
 * @since 1.0.0
 */
final class LoggingMiddleware implements IsMiddleware
{
    /**
     * @param callable(Input, Output): int $next
     */
    #[\Override]
    public function handle(Input $input, Output $output, callable $next): int
    {
        $start = hrtime(true);
        $output->debug('命令开始执行...');

        try {
            return $next($input, $output);
        } finally {
            $elapsed = (hrtime(true) - $start) / 1_000_000;
            $output->debug(sprintf('命令执行完成，耗时 %.2f ms', $elapsed));
        }
    }
}
