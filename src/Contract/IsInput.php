<?php

declare(strict_types=1);

namespace Nova\Console\Contract;

interface IsInput
{
    /**
     * 获取参数值
     */
    public function arg(string $key, mixed $default = null): mixed;

    /**
     * 检查参数是否存在
     */
    public function has(string $key): bool;

    /**
     * 检查标志是否存在
     */
    public function flag(string $name): bool;

    /**
     * 获取选项值
     */
    public function opt(string $name): mixed;

    /**
     * 获取原始参数数组
     *
     * @return array<int, string>
     */
    public function raw(): array;
}