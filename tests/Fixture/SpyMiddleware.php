<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 测试用中间件：执行前在输出中打印标记，便于断言执行顺序
 */
final class SpyMiddleware implements IsMiddleware
{
    public function __construct(private string $tag)
    {
    }

    #[\Override]
    public function handle(Input $input, Output $output, callable $next): int
    {
        $output->line('[MW:' . $this->tag . ']');

        return $next($input, $output);
    }
}
