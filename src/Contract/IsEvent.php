<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

/**
 * 事件契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsEvent
{
    /**
     * 事件名
     */
    public function getName(): string;

    /**
     * 事件上下文
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * 读取单个上下文字段
     */
    public function get(string $key, mixed $default = null): mixed;
}
