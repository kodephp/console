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
     * 类型转换
     */
    public function cast(mixed $value, string $type): mixed
    {
        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'array':
                return explode(',', (string)$value);
            case 'string':
            default:
                return (string)$value;
        }
    }

    /**
     * 验证参数
     */
    public function validate(string $key, mixed $value, array $rules): bool
    {
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'required':
                    if (empty($value)) {
                        return false;
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        return false;
                    }
                    break;
                case 'boolean':
                    if (!is_bool($this->cast($value, 'bool'))) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * 询问用户输入
     */
    public static function ask(string $question, string $default = ''): string
    {
        echo $question;
        if ($default !== '') {
            echo " [{$default}]";
        }
        echo ": ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = trim($input);
        return $input === '' ? $default : $input;
    }

    /**
     * 确认操作
     */
    public static function confirm(string $question, bool $default = false): bool
    {
        $suffix = $default ? 'Y/n' : 'y/N';
        echo "{$question} [{$suffix}]: ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = strtolower(trim($input));
        if ($input === '') {
            return $default;
        }
        
        return in_array($input, ['y', 'yes']);
    }

    /**
     * 选择选项
     * @param array<string, string> $choices
     * @param string|int|null $default
     */
    public static function choice(string $question, array $choices, string|int|null $default = null): string|int|null
    {
        echo $question . "\n";
        
        foreach ($choices as $key => $value) {
            echo "{$key}. {$value}\n";
        }
        
        if ($default !== null) {
            echo "Default: " . (string)$default . "\n";
        }
        
        echo "Enter your choice: ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = trim($input);
        if ($input === '') {
            return $default;
        }
        
        return $choices[$input] ?? $default;
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