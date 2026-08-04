<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

use Kode\Console\Command;

/**
 * 内核契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsKernel
{
    /**
     * 注册命令类
     *
     * @param class-string<Command> $cls
     */
    public function add(string $cls): static;

    /**
     * 注册命令实例
     */
    public function addCommand(Command $command): static;

    /**
     * 运行控制台
     *
     * @param array<int, string> $argv
     */
    public function boot(array $argv): int;

    /**
     * 查找命令
     */
    public function find(string $name): ?Command;

    /**
     * 全部命令
     *
     * @return array<string, Command>
     */
    public function all(): array;
}
