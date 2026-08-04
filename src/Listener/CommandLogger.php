<?php

declare(strict_types=1);

namespace Kode\Console\Listener;

use Kode\Console\Contract\IsEvent;
use JsonException;

/**
 * 命令日志监听器
 *
 * 将事件以 JSON Lines 形式追加写入日志文件，
 * 目录会按需创建，写入失败不会中断命令执行。
 *
 * @package Kode\Console
 * @since 1.0.0
 */
final class CommandLogger
{
    /**
     * @param string $file 日志文件路径
     */
    public function __construct(
        private readonly string $file = 'runtime/logs/command.log',
    ) {
    }

    /**
     * 处理事件
     */
    public function handle(IsEvent $event): void
    {
        $directory = dirname($this->file);

        if (!is_dir($directory) && !@mkdir($directory, 0o755, true) && !is_dir($directory)) {
            return;
        }

        try {
            $payload = json_encode([
                'time' => date('c'),
                'event' => $event->getName(),
                'data' => $this->normalize($event->getData()),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        @file_put_contents($this->file, $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * 过滤掉无法安全序列化的内容
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalized[$key] = match (true) {
                is_scalar($value), $value === null => $value,
                is_array($value) => array_values(array_filter($value, is_scalar(...))),
                is_object($value) => $value::class,
                default => null,
            };
        }

        return $normalized;
    }
}
