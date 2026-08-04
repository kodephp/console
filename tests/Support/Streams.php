<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Support;

use RuntimeException;

/**
 * 内存流工具，便于断言控制台输出
 */
final class Streams
{
    /**
     * 创建可读写的内存流
     *
     * @return resource
     */
    public static function memory(): mixed
    {
        $stream = fopen('php://memory', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('无法创建内存流');
        }

        return $stream;
    }

    /**
     * 以内容预填充的内存流（模拟 STDIN）
     *
     * @return resource
     */
    public static function withContent(string $content): mixed
    {
        $stream = self::memory();
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * 读取流中已写入的全部内容
     *
     * @param resource $stream
     */
    public static function read(mixed $stream): string
    {
        rewind($stream);
        $content = stream_get_contents($stream);

        return $content === false ? '' : $content;
    }
}
