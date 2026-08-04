<?php

declare(strict_types=1);

namespace Kode\Console\Helper;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Exception\InvalidCommandException;
use ReflectionClass;
use ReflectionParameter;

/**
 * 反射工具
 *
 * 集中处理命令类的合法性校验、实例化与注解读取，
 * 并对反射结果做静态缓存，避免重复解析带来的开销。
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
final class Reflector
{
    /**
     * #[AsCommand] 注解缓存
     *
     * @var array<class-string, AsCommand|null>
     */
    private static array $metaCache = [];

    /**
     * 获取命令类的反射对象
     *
     * @template T of Command
     *
     * @param class-string<T> $cls
     *
     * @return ReflectionClass<T>
     *
     * @throws InvalidCommandException
     */
    public static function of(string $cls): ReflectionClass
    {
        if (!class_exists($cls)) {
            throw InvalidCommandException::classNotFound($cls);
        }

        $ref = new ReflectionClass($cls);

        if (!$ref->isSubclassOf(Command::class)) {
            throw InvalidCommandException::notACommand($cls);
        }

        if (!$ref->isInstantiable()) {
            throw InvalidCommandException::notInstantiable($cls);
        }

        return $ref;
    }

    /**
     * 实例化命令类
     *
     * @param class-string<Command> $cls
     *
     * @throws InvalidCommandException
     */
    public static function instantiate(string $cls): Command
    {
        $ref = self::of($cls);
        $constructor = $ref->getConstructor();

        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            throw InvalidCommandException::notInstantiable($cls);
        }

        return $ref->newInstance();
    }

    /**
     * 类是否可实例化
     */
    public static function isInstantiable(string $cls): bool
    {
        if (!class_exists($cls)) {
            return false;
        }

        return (new ReflectionClass($cls))->isInstantiable();
    }

    /**
     * 读取类上的 #[AsCommand] 注解
     *
     * @param class-string $cls
     */
    public static function commandMeta(string $cls): ?AsCommand
    {
        if (array_key_exists($cls, self::$metaCache)) {
            return self::$metaCache[$cls];
        }

        $meta = null;

        if (class_exists($cls)) {
            $attributes = (new ReflectionClass($cls))->getAttributes(AsCommand::class);

            if ($attributes !== []) {
                $meta = $attributes[0]->newInstance();
            }
        }

        return self::$metaCache[$cls] = $meta;
    }

    /**
     * 获取构造函数参数
     *
     * @param class-string<Command> $cls
     *
     * @return array<int, ReflectionParameter>
     */
    public static function getConstructorParameters(string $cls): array
    {
        $constructor = self::of($cls)->getConstructor();

        return $constructor === null ? [] : $constructor->getParameters();
    }

    /**
     * 清空注解缓存（主要供测试使用）
     */
    public static function flush(): void
    {
        self::$metaCache = [];
    }
}
