<?php

declare(strict_types=1);

namespace Kode\Console;

/**
 * 命令签名解析器
 * 
 * 使用 DSL 风格定义命令的参数和选项。
 * 支持参数类型、默认值、必填/可选等特性。
 * 
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 * 
 * @phpstan-type ArgumentDefinition array{required: bool, default: mixed, type: string}
 * @phpstan-type OptionDefinition array{value_required: bool, default: mixed, type: string}
 */
class Signature
{
    /**
     * 命令名称
     */
    protected string $name = '';
    
    /**
     * 原始签名定义
     */
    protected string $definition;
    
    /**
     * 参数定义列表
     * 
     * @var array<string, ArgumentDefinition>
     */
    protected array $arguments = [];
    
    /**
     * 选项定义列表
     * 
     * @var array<string, OptionDefinition>
     */
    protected array $options = [];
    
    /**
     * 标志别名映射
     * 
     * @var array<string, string>
     */
    protected array $flags = [];

    /**
     * 构造函数
     * 
     * @param string $definition 签名定义字符串
     */
    public function __construct(string $definition)
    {
        $this->definition = $definition;
        $this->parse($definition);
    }

    /**
     * 解析命令签名
     * 
     * 解析签名定义字符串，提取命令名、参数和选项。
     * 
     * 示例：
     * - `serve {app?} {--port=8080}`
     * - `migrate {name:string} {--force:bool}`
     * 
     * @param string $signature 签名定义字符串
     */
    protected function parse(string $signature): void
    {
        // 使用正则表达式分割命令定义和参数/选项
        preg_match('/^([^\s]+)(?:\s+(.+))?$/', $signature, $matches);
        
        $this->name = $matches[1] ?? '';
        $definition = $matches[2] ?? '';
        
        if ($definition) {
            /** @var list<string>|false $tokens */
            $tokens = preg_split('/\s+/', $definition);
            if ($tokens !== false) {
                foreach ($tokens as $token) {
                    if (str_starts_with($token, '{')) {
                        $param = trim($token, '{}');
                        if (str_starts_with($param, '--')) {
                            $this->parseOption($param);
                        } else {
                            $this->parseArgument($param);
                        }
                    }
                }
            }
        }
    }

    /**
     * 解析参数定义
     * 
     * 支持的格式：
     * - `{name}` - 必填参数
     * - `{name?}` - 可选参数
     * - `{name=default}` - 带默认值的参数
     * - `{name:string}` - 带类型的参数
     * - `{name:string=default}` - 带类型和默认值的参数
     * 
     * @param string $definition 参数定义字符串
     */
    protected function parseArgument(string $definition): void
    {
        $argument = [
            'required' => true,
            'default' => null,
            'type' => 'string',
        ];

        // 处理可选参数 {argument?}
        if (str_ends_with($definition, '?')) {
            $argument['required'] = false;
            $definition = substr($definition, 0, -1);
        }

        // 处理类型 {argument:string}
        if (str_contains($definition, ':')) {
            [$name, $type] = explode(':', $definition, 2);
            $argument['type'] = $type;
        } else {
            $name = $definition;
        }

        // 处理默认值 {argument=default} 或 {argument:string=default}
        if (str_contains($name, '=')) {
            [$name, $default] = explode('=', $name, 2);
            $argument['required'] = false;
            $argument['default'] = $default;
        }

        $this->arguments[$name] = $argument;
    }

    /**
     * 解析选项定义
     * 
     * 支持的格式：
     * - `{--option}` - 标志选项
     * - `{--option=}` - 必填值选项
     * - `{--option=default}` - 带默认值的选项
     * - `{--option:string}` - 带类型的选项
     * - `{--option|-o}` - 带短别名的选项
     * 
     * @param string $definition 选项定义字符串
     */
    protected function parseOption(string $definition): void
    {
        $option = [
            'value_required' => false,
            'default' => null,
            'type' => 'string',
        ];

        // 处理带值的选项 {--option=}
        if (str_ends_with($definition, '=')) {
            $option['value_required'] = true;
            $name = substr($definition, 0, -1);
        }
        // 处理带默认值的选项 {--option=default}
        elseif (str_contains($definition, '=')) {
            [$name, $default] = explode('=', $definition, 2);
            $option['value_required'] = false;
            $option['default'] = $default;
        } else {
            // 标志选项 {--option}
            $name = $definition;
        }

        // 处理类型 {--option:string}
        if (str_contains($name, ':')) {
            [$name, $type] = explode(':', $name, 2);
            $option['type'] = $type;
        }

        // 处理 {--option|-o} 格式
        if (str_contains($name, '|')) {
            $parts = explode('|', $name);
            $name = ltrim($parts[0], '-');
            if (isset($parts[1])) {
                $alias = ltrim($parts[1], '-');
                $this->flags[$alias] = $name; // 别名映射到主名称
            }
        } else {
            // 移除可能的横线前缀
            $name = ltrim($name, '-');
        }

        $this->options[$name] = $option;
    }

    /**
     * 获取命令名称
     * 
     * @return string 命令名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取参数定义
     * 
     * @return array<string, ArgumentDefinition> 参数定义列表
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * 获取选项定义
     * 
     * @return array<string, OptionDefinition> 选项定义列表
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 获取标志别名映射
     * 
     * @return array<string, string> 别名到主名称的映射
     */
    public function getFlags(): array
    {
        return $this->flags;
    }
}
