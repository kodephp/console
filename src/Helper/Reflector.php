<?php

declare(strict_types=1);

namespace Nova\Console\Helper;

use Nova\Console\Command;
use ReflectionClass;
use InvalidArgumentException;

class Reflector
{
    /**
     * 安全获取类的公共属性与方法
     * 
     * @template T of object
     * @param class-string<T> $cls
     * @return ReflectionClass<T>
     * @phpstan-param class-string<T> $cls
     */
    /**
     * @template T of object
     * @param class-string<T> $cls
     * @return ReflectionClass<T>
     * @phpstan-param class-string<T> $cls
     */
    public static function of(string $cls): ReflectionClass
    {
        if (!class_exists($cls)) {
            throw new InvalidArgumentException("Class {$cls} not found.");
        }

        $ref = new ReflectionClass($cls);
        if (!$ref->isSubclassOf(Command::class)) {
            throw new InvalidArgumentException("{$cls} must extend Command.");
        }

        return $ref;
    }

    /**
     * 检查类是否可以实例化
     */
    public static function isInstantiable(string $cls): bool
    {
        if (!class_exists($cls)) {
            return false;
        }

        $ref = new ReflectionClass($cls);
        return $ref->isInstantiable();
    }

    /**
     * 获取类的构造函数参数
     *
     * @param class-string<Command> $cls
     * @return array<int, \ReflectionParameter>
     */
    public static function getConstructorParameters(string $cls): array
    {
        /** @var ReflectionClass<Command> $ref */
        $ref = self::of($cls);
        
        if (!$ref->hasMethod('__construct')) {
            return [];
        }
        
        $constructor = $ref->getMethod('__construct');
        return $constructor->getParameters();
    }
}