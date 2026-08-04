<?php

declare(strict_types=1);

namespace Kode\Console\Enum;

use Stringable;

/**
 * 参数类型
 *
 * 用于命令签名 DSL 的类型标注与输入值的自动转换。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
enum ArgType: string
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Array = 'array';
    case Json = 'json';

    /**
     * 由类型名解析枚举，兼容 integer/double/boolean 等别名
     *
     * 无法识别的类型一律降级为 {@see self::String}，保证解析器永不抛错。
     */
    public static function parse(?string $name): self
    {
        return match (strtolower(trim((string) $name))) {
            'int', 'integer' => self::Int,
            'float', 'double', 'number' => self::Float,
            'bool', 'boolean' => self::Bool,
            'array', 'list', 'csv' => self::Array,
            'json' => self::Json,
            default => self::String,
        };
    }

    /**
     * 将任意输入值转换为当前类型
     *
     * `null` 永远原样返回，便于区分「未提供」与「提供了空值」。
     */
    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::String => self::toString($value),
            self::Int => (int) self::toNumeric($value),
            self::Float => self::toNumeric($value),
            self::Bool => self::toBool($value),
            self::Array => self::toArray($value),
            self::Json => self::toJson($value),
        };
    }

    /**
     * 类型标注是否需要显式书写（string 为默认类型，帮助信息中可省略）
     */
    public function isDefault(): bool
    {
        return $this === self::String;
    }

    /**
     * 安全的字符串化
     */
    private static function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(',', array_map(self::toString(...), $value));
        }

        return '';
    }

    /**
     * 安全的数值化：非数值内容统一按 0 处理
     */
    private static function toNumeric(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        $text = trim(self::toString($value));

        return is_numeric($text) ? (float) $text : 0.0;
    }

    /**
     * 布尔化：识别 1/true/yes/on 等常见写法
     */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        $filtered = filter_var(self::toString($value), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $filtered ?? self::toString($value) !== '';
    }

    /**
     * 数组化：逗号分隔字符串会被拆分并去除首尾空白
     *
     * @return array<int, mixed>
     */
    private static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        $text = self::toString($value);

        if ($text === '') {
            return [];
        }

        return array_map(trim(...), explode(',', $text));
    }

    /**
     * JSON 解码，非法 JSON 返回 null（PHP 8.3 的 json_validate 零拷贝校验）
     */
    private static function toJson(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        $text = self::toString($value);

        if ($text === '' || !json_validate($text)) {
            return null;
        }

        return json_decode($text, true);
    }
}
