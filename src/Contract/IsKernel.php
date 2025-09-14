<?php

declare(strict_types=1);

namespace Nova\Console\Contract;

use Nova\Console\Command;

interface IsKernel
{
    /**
     * 注册命令
     *
     * @param class-string<Command> $cls
     */
    public function add(string $cls): static;

    /**
     * 运行控制台
     *
     * @param array<int, string> $argv
     */
    public function boot(array $argv): int;

    /**
     * 获取所有命令
     *
     * @return iterable<Command>
     */
    public function all(): iterable;
}