<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsCommand;
use InvalidArgumentException;

/**
 * 命令基类
 * 
 * 所有控制台命令都必须继承此类，并实现 fire() 方法。
 * 支持命令签名、别名、示例、相关命令等功能。
 * 
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
abstract class Command implements IsCommand
{
    /**
     * 命令名称
     * 
     * @readonly
     * @var string 命令名，如 "app:serve"
     */
    public readonly string $name;
    
    /**
     * 命令描述
     * 
     * @readonly
     * @var string 命令的简短描述
     */
    public readonly string $desc;
    
    /**
     * 用法说明
     * 
     * @readonly
     * @var string 命令的使用方法说明
     */
    public readonly string $usage;

    /**
     * 命令别名列表
     * 
     * @var array<int, string>
     */
    protected array $aliases = [];
    
    /**
     * 使用示例列表
     * 
     * @var array<int, array{example: string, description: string}>
     */
    protected array $examples = [];
    
    /**
     * 相关命令列表
     * 
     * @var array<int, string>
     */
    protected array $related = [];
    
    /**
     * 命令所属分组
     */
    protected ?string $group = null;
    
    /**
     * 命令签名对象
     */
    protected ?Signature $signature = null;

    /**
     * 构造函数
     * 
     * @param string $name 命令名称
     * @param string $desc 命令描述
     * @param string $usage 用法说明
     */
    public function __construct(
        string $name = '',
        string $desc = '',
        string $usage = ''
    ) {
        $this->name = $name;
        $this->desc = $desc;
        $this->usage = $usage;
    }

    /**
     * 执行命令
     * 
     * 这是命令的核心方法，必须在子类中实现。
     * 
     * @param Input $in 输入对象
     * @param Output $out 输出对象
     * @return int 返回退出码，0 表示成功，非零表示失败
     */
    abstract public function fire(Input $in, Output $out): int;

    /**
     * 注册命令签名
     * 
     * 使用 DSL 风格定义命令的参数和选项。
     * 
     * 示例：
     * - `serve {app?} {--port=8080}` - 可选参数和带默认值的选项
     * - `migrate {name:string} {--force:bool}` - 带类型的参数和布尔选项
     * 
     * @param string $def 签名定义字符串
     * @return static 返回当前实例，支持链式调用
     */
    public function sig(string $def): static
    {
        $this->signature = new Signature($def);
        return $this;
    }

    /**
     * 设置命令描述
     * 
     * @param string $text 描述文本
     * @return static 返回当前实例，支持链式调用
     */
    public function about(string $text): static
    {
        return $this;
    }

    /**
     * 设置命令别名
     * 
     * @param array<int, string> $alias 别名列表
     * @return static 返回当前实例，支持链式调用
     */
    public function alias(array $alias): static
    {
        $this->aliases = $alias;
        return $this;
    }

    /**
     * 获取命令别名列表
     * 
     * @return array<int, string> 别名列表
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * 添加使用示例
     * 
     * @param string $example 示例命令
     * @param string $description 示例描述
     * @return static 返回当前实例，支持链式调用
     */
    public function example(string $example, string $description = ''): static
    {
        $this->examples[] = ['example' => $example, 'description' => $description];
        return $this;
    }

    /**
     * 获取命令示例列表
     * 
     * @return array<int, array{example: string, description: string}> 示例列表
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * 设置相关命令
     * 
     * @param array<int, string> $commands 相关命令列表
     * @return static 返回当前实例，支持链式调用
     */
    public function related(array $commands): static
    {
        $this->related = $commands;
        return $this;
    }

    /**
     * 获取相关命令列表
     * 
     * @return array<int, string> 相关命令列表
     */
    public function getRelated(): array
    {
        return $this->related;
    }

    /**
     * 设置命令分组
     * 
     * @param string $group 分组名称
     * @return static 返回当前实例，支持链式调用
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * 获取命令分组
     * 
     * @return string|null 分组名称，未设置时返回 null
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * 显示详细帮助信息
     * 
     * 输出命令的完整帮助信息，包括用法、参数、选项、示例等。
     * 
     * @param Input $in 输入对象
     * @param Output $out 输出对象
     */
    public function showHelp(Input $in, Output $out): void
    {
        $out->line("命令: {$this->name}", 'bold');
        $out->line("描述: {$this->desc}");
        
        if (!empty($this->usage)) {
            $out->line("用法: {$this->usage}");
        }
        
        if (!empty($this->aliases)) {
            $out->line("别名: " . implode(', ', $this->aliases));
        }
        
        // 显示签名参数详情
        if (isset($this->signature)) {
            $out->line("\n参数和选项:");
            
            // 显示参数
            $arguments = $this->signature->getArguments();
            if (!empty($arguments)) {
                $out->line("参数:");
                foreach ($arguments as $name => $def) {
                    $label = $def['required'] ? "{$name}" : "{$name}?";
                    $type = $def['type'] !== 'string' ? " ({$def['type']})" : "";
                    $default = $def['default'] !== null ? " [默认: {$def['default']}]" : "";
                    $out->line("  {$label}{$type}{$default}");
                }
            }
            
            // 显示选项
            $options = $this->signature->getOptions();
            if (!empty($options)) {
                $out->line("\n选项:");
                foreach ($options as $name => $def) {
                    $label = "--{$name}";
                    $type = $def['type'] !== 'string' ? " ({$def['type']})" : "";
                    $default = $def['default'] !== null ? " [默认: {$def['default']}]" : "";
                    $out->line("  {$label}{$type}{$default}");
                }
            }
        }
        
        if (!empty($this->examples)) {
            $out->line("\n示例:");
            foreach ($this->examples as $example) {
                $out->line("  {$example['example']}");
                if (!empty($example['description'])) {
                    $out->line("    {$example['description']}");
                }
            }
        }
        
        if (!empty($this->related)) {
            $out->line("\n相关命令:");
            foreach ($this->related as $command) {
                $out->line("  {$command}");
            }
        }
    }

    /**
     * 验证输入参数
     * 
     * 根据签名定义验证输入参数是否符合要求。
     * 
     * @param Input $in 输入对象
     * @param Output $out 输出对象
     * @return bool 验证通过返回 true，否则返回 false
     */
    protected function validateInput(Input $in, Output $out): bool
    {
        if (!$this->signature) {
            return true;
        }

        $arguments = $this->signature->getArguments();
        $options = $this->signature->getOptions();

        // 验证参数
        foreach ($arguments as $name => $def) {
            $value = $in->arg($name);
            if ($def['required'] && $value === null) {
                $out->error("参数 '{$name}' 是必需的。");
                return false;
            }
        }

        // 验证选项
        foreach ($options as $name => $def) {
            if ($def['value_required'] && $in->opt($name) === null) {
                $out->error("选项 '--{$name}' 需要一个值。");
                return false;
            }
        }

        return true;
    }

    /**
     * 格式化输出错误信息
     * 
     * @param Input $in 输入对象
     * @param Output $out 输出对象
     * @param string $message 错误信息
     * @param int $code 退出码，默认为 1
     * @return int 返回退出码
     */
    protected function error(Input $in, Output $out, string $message, int $code = 1): int
    {
        $out->error($message);
        return $code;
    }
}
