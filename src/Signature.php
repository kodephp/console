<?php

declare(strict_types=1);

namespace Nova\Console;

class Signature
{
    protected string $name = '';
    protected string $definition;
    protected array $arguments = [];
    protected array $options = [];
    protected array $flags = [];

    public function __construct(string $definition)
    {
        $this->definition = $definition;
        $this->parse($definition);
    }

    /**
     * 解析命令签名
     */
    protected function parse(string $signature): void
    {
        // 使用正则表达式分割命令定义和参数/选项
        preg_match('/^([^\s]+)(?:\s+(.+))?$/', $signature, $matches);
        
        $this->name = $matches[1] ?? '';
        $definition = $matches[2] ?? '';
        
        if ($definition) {
            $tokens = preg_split('/\s+/', $definition);
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

    /**
     * 解析参数
     */
    protected function parseArgument(string $definition): void
    {
        $argument = [
            'required' => true,
            'default' => null,
        ];

        // 处理可选参数 {argument?}
        if (str_ends_with($definition, '?')) {
            $argument['required'] = false;
            $definition = substr($definition, 0, -1);
        }

        // 处理默认值 {argument=default}
        if (str_contains($definition, '=')) {
            [$name, $default] = explode('=', $definition, 2);
            $argument['required'] = false;
            $argument['default'] = $default;
        } else {
            $name = $definition;
        }

        $this->arguments[$name] = $argument;
    }

    /**
     * 解析选项
     */
    protected function parseOption(string $definition): void
    {
        $option = [
            'value_required' => false,
            'default' => null,
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
     * 获取参数定义
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * 获取选项定义
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 获取标志定义
     */
    public function getFlags(): array
    {
        return $this->flags;
    }
}