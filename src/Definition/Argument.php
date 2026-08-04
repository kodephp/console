<?php

declare(strict_types=1);

namespace Kode\Console\Definition;

use Kode\Console\Enum\ArgType;

/**
 * 位置参数定义
 *
 * 由 {@see \Kode\Console\Signature} 解析签名 DSL 后生成，不可变。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
final readonly class Argument
{
    /**
     * @param string  $name        参数名
     * @param ArgType $type        参数类型
     * @param bool    $required    是否必填
     * @param mixed   $default     默认值（已按类型转换）
     * @param bool    $variadic    是否为可变参数（`{files*}`，吞掉其后所有位置参数）
     * @param string  $description 参数说明
     */
    public function __construct(
        public string $name,
        public ArgType $type = ArgType::String,
        public bool $required = true,
        public mixed $default = null,
        public bool $variadic = false,
        public string $description = '',
    ) {
    }

    /**
     * 帮助信息中展示的形态，如 `name`、`name?`、`files...`
     */
    public function label(): string
    {
        if ($this->variadic) {
            return $this->name . '...';
        }

        return $this->required ? $this->name : $this->name . '?';
    }
}
