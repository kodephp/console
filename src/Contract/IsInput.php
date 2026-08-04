<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

use Kode\Console\Signature;

/**
 * 输入契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsInput
{
    /**
     * 绑定命令签名
     */
    public function bind(Signature $signature): static;

    /**
     * 获取参数值（整数按位置，字符串按签名命名）
     */
    public function arg(string|int $key, mixed $default = null): mixed;

    /**
     * 全部命名参数
     *
     * @return array<string, mixed>
     */
    public function args(): array;

    /**
     * 获取选项值
     */
    public function opt(string $name, mixed $default = null): mixed;

    /**
     * 全部选项
     *
     * @return array<string, mixed>
     */
    public function options(): array;

    /**
     * 检查标志
     */
    public function flag(string $name, bool $default = false): bool;

    /**
     * 参数 / 选项 / 标志是否存在
     */
    public function has(string|int $key): bool;

    /**
     * `--` 之后的原样参数
     *
     * @return array<int, string>
     */
    public function rest(): array;

    /**
     * 原始参数数组
     *
     * @return array<int, string>
     */
    public function raw(): array;
}
