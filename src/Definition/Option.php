<?php

declare(strict_types=1);

namespace Kode\Console\Definition;

use Kode\Console\Enum\ArgType;

/**
 * 选项定义
 *
 * 由 {@see \Kode\Console\Signature} 解析签名 DSL 后生成，不可变。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
final readonly class Option
{
    /**
     * @param string      $name          选项长名（不含 `--`）
     * @param string|null $shortcut      短别名（不含 `-`），如 `p`
     * @param ArgType     $type          值类型
     * @param bool        $acceptsValue  是否接受值（false 表示纯布尔标志）
     * @param bool        $valueRequired 是否必须显式提供值
     * @param mixed       $default       默认值（已按类型转换）
     * @param string      $description   选项说明
     */
    public function __construct(
        public string $name,
        public ?string $shortcut = null,
        public ArgType $type = ArgType::String,
        public bool $acceptsValue = false,
        public bool $valueRequired = false,
        public mixed $default = null,
        public string $description = '',
    ) {
    }

    /**
     * 帮助信息中展示的形态，如 `-p, --port[=PORT]`
     */
    public function label(): string
    {
        $label = $this->shortcut !== null ? "-{$this->shortcut}, --{$this->name}" : "    --{$this->name}";

        if ($this->acceptsValue) {
            $placeholder = strtoupper(str_replace('-', '_', $this->name));
            $label .= $this->valueRequired ? "={$placeholder}" : "[={$placeholder}]";
        }

        return $label;
    }
}
