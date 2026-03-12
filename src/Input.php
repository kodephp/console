<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsInput;

/**
 * 输入解析器
 * 
 * 负责解析命令行参数，提供便捷的方法获取参数、选项和标志。
 * 支持类型转换和验证功能。
 * 
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 * 
 * @phpstan-type ArgValue string
 * @phpstan-type FlagValue bool
 * @phpstan-type OptionValue string
 */
class Input implements IsInput
{
    /**
     * 位置参数列表
     * 
     * @var array<int, ArgValue>
     */
    protected array $args = [];
    
    /**
     * 标志列表（布尔选项）
     * 
     * @var array<string, FlagValue>
     */
    protected array $flags = [];
    
    /**
     * 选项列表（带值选项）
     * 
     * @var array<string, OptionValue>
     */
    protected array $options = [];
    
    /**
     * 原始参数数组
     * 
     * @var array<int, string>
     */
    protected array $raw = [];

    /**
     * 构造函数
     * 
     * @param array<int, string> $argv 命令行参数数组
     */
    public function __construct(array $argv)
    {
        $this->raw = $argv;
        $this->parse($argv);
    }

    /**
     * 解析命令行参数
     * 
     * 将命令行参数解析为位置参数、选项和标志。
     * 
     * 支持的格式：
     * - 位置参数：`arg1 arg2`
     * - 长选项：`--option=value` 或 `--option value`
     * - 短标志：`-v` 或 `-abc`（等同于 `-a -b -c`）
     * 
     * @param array<int, string> $argv 命令行参数数组
     */
    protected function parse(array $argv): void
    {
        // 移除脚本名称
        array_shift($argv);

        $this->args = [];
        $this->flags = [];
        $this->options = [];

        $positionalIndex = 0;
        $argc = count($argv);
        
        for ($i = 0; $i < $argc; $i++) {
            $arg = $argv[$i];

            // 处理长选项 --option=value 或 --option value
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                
                if (isset($parts[1])) {
                    // 格式: --option=value
                    $this->options[$key] = $parts[1];
                } else {
                    // 格式: --option value 或 --flag
                    if ($i + 1 < $argc && !str_starts_with($argv[$i + 1], '-') && !str_starts_with($argv[$i + 1], '--')) {
                        // 下一个参数是值
                        $this->options[$key] = $argv[$i + 1];
                        $i++; // 跳过下一个参数
                    } else {
                        // 没有值，当作标志处理
                        $this->flags[$key] = true;
                    }
                }
            }
            // 处理短标志 -v 或 -abc
            elseif (str_starts_with($arg, '-')) {
                $flags = substr($arg, 1);
                // 每个字符都是一个标志
                for ($j = 0, $len = strlen($flags); $j < $len; $j++) {
                    $this->flags[$flags[$j]] = true;
                }
            }
            // 位置参数（从索引 0 开始）
            else {
                $this->args[$positionalIndex] = $arg;
                $positionalIndex++;
            }
        }
    }

    /**
     * 类型转换
     * 
     * 将值转换为指定的类型。
     * 
     * 支持的类型：
     * - int/integer：整数
     * - float/double：浮点数
     * - bool/boolean：布尔值
     * - array：数组（逗号分隔）
     * - string：字符串（默认）
     * 
     * @param mixed $value 要转换的值
     * @param string $type 目标类型
     * @return mixed 转换后的值
     */
    public function cast(mixed $value, string $type): mixed
    {
        return match (strtolower($type)) {
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : explode(',', (string)$value),
            default => (string)$value,
        };
    }

    /**
     * 验证参数
     * 
     * 根据规则验证参数值。
     * 
     * 支持的规则：
     * - required：必填
     * - numeric：必须是数字
     * - boolean：必须是布尔值
     * 
     * @param string $key 参数名
     * @param mixed $value 参数值
     * @param array<int, string> $rules 验证规则列表
     * @return bool 验证通过返回 true，否则返回 false
     */
    public function validate(string $key, mixed $value, array $rules): bool
    {
        foreach ($rules as $rule) {
            $passed = match ($rule) {
                'required' => !empty($value),
                'numeric' => is_numeric($value),
                'boolean' => is_bool($this->cast($value, 'bool')),
                default => true,
            };
            
            if (!$passed) {
                return false;
            }
        }
        return true;
    }

    /**
     * 询问用户输入
     * 
     * 在控制台显示问题并等待用户输入。
     * 
     * @param string $question 问题文本
     * @param string $default 默认值
     * @return string 用户输入或默认值
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
     * 
     * 显示确认问题，等待用户输入 y/n。
     * 
     * @param string $question 问题文本
     * @param bool $default 默认值
     * @return bool 用户确认返回 true，否则返回 false
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
        
        return in_array($input, ['y', 'yes'], true);
    }

    /**
     * 选择选项
     * 
     * 显示选项列表，等待用户选择。
     * 
     * @param string $question 问题文本
     * @param array<string, string> $choices 选项列表 [key => label]
     * @param string|int|null $default 默认选项
     * @return string|int|null 用户选择的选项或默认值
     */
    public static function choice(string $question, array $choices, string|int|null $default = null): string|int|null
    {
        echo $question . "\n";
        
        foreach ($choices as $key => $value) {
            echo "{$key}. {$value}\n";
        }
        
        if ($default !== null) {
            echo "默认: " . (string)$default . "\n";
        }
        
        echo "请输入选择: ";
        
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
     * 
     * 根据索引或名称获取参数值。
     * 
     * @param string|int $key 参数索引或名称
     * @param mixed $default 默认值
     * @return mixed 参数值或默认值
     */
    public function arg(string|int $key, mixed $default = null): mixed
    {
        if (is_int($key)) {
            return $this->args[$key] ?? $default;
        }
        
        // 对于命名参数，需要在解析时建立映射
        return $default;
    }

    /**
     * 检查参数是否存在
     * 
     * @param string $key 参数名
     * @return bool 存在返回 true，否则返回 false
     */
    public function has(string $key): bool
    {
        if (is_numeric($key)) {
            return isset($this->args[(int)$key]);
        }
        
        return isset($this->args[$key]) || isset($this->options[$key]);
    }

    /**
     * 检查标志是否存在
     * 
     * @param string $name 标志名
     * @return bool 存在返回 true，否则返回 false
     */
    public function flag(string $name): bool
    {
        return isset($this->flags[$name]);
    }

    /**
     * 获取选项值
     * 
     * @param string $name 选项名
     * @return mixed 选项值，不存在时返回 null
     */
    public function opt(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * 获取原始参数数组
     * 
     * @return array<int, string> 原始参数数组
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
