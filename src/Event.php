<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsEvent;

/**
 * 事件对象
 *
 * 不可变值对象，仅承载事件名与上下文数据。
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
final readonly class Event implements IsEvent
{
    /**
     * @param array<string, mixed> $data 事件上下文
     */
    public function __construct(
        private string $name,
        private array $data = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 读取单个上下文字段
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 上下文字段是否存在
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
