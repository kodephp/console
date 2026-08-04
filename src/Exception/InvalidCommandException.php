<?php

declare(strict_types=1);

namespace Kode\Console\Exception;

/**
 * 命令定义非法
 *
 * 例如：类不存在、未继承 Command、缺少命令名、命令重复注册等。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
final class InvalidCommandException extends ConsoleException
{
    public static function classNotFound(string $class): self
    {
        return new self("命令类 {$class} 不存在。");
    }

    public static function notACommand(string $class): self
    {
        return new self("{$class} 必须继承 Kode\\Console\\Command。");
    }

    public static function notInstantiable(string $class): self
    {
        return new self("命令类 {$class} 无法实例化（抽象类或构造函数不可访问）。");
    }

    public static function missingName(string $class): self
    {
        return new self("命令类 {$class} 未定义命令名，请通过构造参数或 #[AsCommand] 指定。");
    }

    public static function duplicated(string $name): self
    {
        return new self("命令 '{$name}' 已被注册，不能重复注册。");
    }
}
