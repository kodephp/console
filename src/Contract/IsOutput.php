<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

use Kode\Console\Enum\Color;
use Kode\Console\Enum\Verbosity;

/**
 * 输出契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsOutput
{
    /**
     * 输出一行文本
     */
    public function line(string $text = '', Color|string $color = '', Verbosity $level = Verbosity::Normal): void;

    /**
     * 写入文本（不换行）
     */
    public function write(string $text, Color|string|null $color = null, Verbosity $level = Verbosity::Normal): void;

    /**
     * 输出若干空行
     */
    public function newLine(int $count = 1): void;

    /**
     * 信息
     */
    public function info(string $msg): void;

    /**
     * 警告
     */
    public function warn(string $msg): void;

    /**
     * 错误
     */
    public function error(string $msg): void;

    /**
     * 成功
     */
    public function success(string $msg): void;

    /**
     * 原始文本（不换行、不着色）
     */
    public function raw(string $text): void;

    /**
     * 是否着色输出
     */
    public function isDecorated(): bool;

    /**
     * 当前详细度
     */
    public function getVerbosity(): Verbosity;
}
