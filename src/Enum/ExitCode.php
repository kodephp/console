<?php

declare(strict_types=1);

namespace Kode\Console\Enum;

/**
 * 标准退出码
 *
 * 遵循 POSIX / sysexits 常见约定，避免在业务代码里散落魔法数字。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
enum ExitCode: int
{
    /** 执行成功 */
    case Success = 0;

    /** 一般性失败 */
    case Failure = 1;

    /** 输入非法（缺少必填参数、类型不符等） */
    case InvalidInput = 2;

    /** 命令未找到 */
    case NotFound = 127;

    /**
     * 是否为成功状态
     */
    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    /**
     * 将枚举或整数统一归一化为整数退出码
     */
    public static function normalize(int|self $code): int
    {
        return $code instanceof self ? $code->value : $code;
    }
}
