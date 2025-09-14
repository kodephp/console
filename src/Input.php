<?php

namespace Kode\Console;

use Kode\Console\Contract\IsInput;

/**
 * @phpstan-type ArgValue string
 * @phpstan-type FlagValue bool
 * @phpstan-type OptionValue string
 */
class Input implements IsInput
{
    /** @var array<int, ArgValue> */
    protected array $args = [];
    /** @var array<string, FlagValue> */
    protected array $flags = [];
    /** @var array<string, OptionValue> */
    protected array $options = [];
    /** @var array<int, string> */
    protected array $raw = [];

    /**
     * @param array<int, string> $argv
     */
    public function __construct(array $argv)
    {
        $this->raw = $argv;
        $this->parse($argv);
    }

    /**
     * 解析命令行参数
     *
     * @param array<int, string> $argv
     */
    protected function parse(array $argv): void
    {
        // 移除脚本名称
        array_shift($argv);

        $this->args = [];
        $this->flags = [];
        $this->options = [];

        $positionalIndex = 0;
        for ($i = 0; $i < count($argv); $i++) {
            $arg = $argv[$i];

            // 处理选项 --option=value 或 --option value
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                
                if (isset($parts[1])) {
                    // 格式: --option=value
                    $this->options[$key] = $parts[1];
                } else {
                    // 格式: --option value 或 --flag
                    if ($i + 1 < count($argv) && !str_starts_with($argv[$i + 1], '-') && !str_starts_with($argv[$i + 1], '--')) {
                        // 下一个参数是值
                        $this->options[$key] = $argv[$i + 1];
                        $i++; // 跳过下一个参数
                    } else {
                        // 没有值，当作标志处理
                        $this->flags[$key] = true;
                    }
                }
            }
            // 处理标志 -v 或 -abc
            elseif (str_starts_with($arg, '-')) {
                $flags = substr($arg, 1);
                // 简单处理，每个字符都是一个标志
                for ($j = 0; $j < strlen($flags); $j++) {
                    $this->flags[$flags[$j]] = true;
                }
            }
            // 位置参数 (从索引0开始)
            else {
                $this->args[$positionalIndex] = $arg;
                $positionalIndex++;
            }
        }
    }

    /**
     * 获取参数值
     */
    public function arg(string|int $key, mixed $default = null): mixed
    {
        if (is_numeric($key)) {
            return $this->args[$key] ?? $default;
        }
        
        // 对于命名参数，我们需要在解析时建立映射
        // 这里简化处理，实际应该通过 Signature 解析建立映射
        return $default;
    }

    /**
     * 检查参数是否存在
     */
    public function has(string $key): bool
    {
        if (is_numeric($key)) {
            return isset($this->args[$key]);
        }
        
        return isset($this->args[$key]) || isset($this->options[$key]);
    }

    /**
     * 检查标志是否存在
     */
    public function flag(string $name): bool
    {
        return isset($this->flags[$name]);
    }

    /**
     * 获取选项值
     */
    public function opt(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * 获取原始参数数组
     *
     * @return array<int, string>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}