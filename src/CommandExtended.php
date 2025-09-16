<?php

namespace Kode\Console;

use Kode\Console\Contract\IsCommand;
use InvalidArgumentException;

abstract class CommandExtended extends Command implements IsCommand
{
    /**
     * @var string[]
     */
    protected array $aliases = [];
    
    /**
     * @var array<array{example: string, description: string}>
     */
    protected array $examples = [];
    
    /**
     * @var string[]
     */
    protected array $related = [];
    
    protected ?string $group = null;

    /**
     * 设置命令别名
     */
    /**
     * @param string[] $alias
     */
    public function alias(array $alias): static
    {
        $this->aliases = $alias;

        return $this;
    }

    /**
     * 获取命令别名
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * 添加使用示例
     */
    public function example(string $example, string $description = ''): static
    {
        $this->examples[] = ['example' => $example, 'description' => $description];
        return $this;
    }

    /**
     * 获取命令示例
     * @return array<array{example: string, description: string}>
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * 设置相关命令
     */
    /**
     * @param string[] $commands
     */
    public function related(array $commands): static
    {
        $this->related = $commands;

        return $this;
    }

    /**
     * 获取相关命令
     * @return string[]
     */
    public function getRelated(): array
    {
        return $this->related;
    }

    /**
     * 设置命令组
     */
    public function group(string $group): static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * 获取命令组
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * 显示详细帮助信息
     */
    public function showHelp(Input $in, Output $out): void
    {
        $out->line("Command: {$this->name}", 'bold');
        $out->line("Description: {$this->desc}");
        
        if (!empty($this->usage)) {
            $out->line("Usage: {$this->usage}");
        }
        
        if (!empty($this->aliases)) {
            $out->line("Aliases: " . implode(', ', $this->aliases));
        }
        
        // 显示签名参数详情
        if (isset($this->signature)) {
            $out->line("\nArguments and Options:");
            // 这里可以解析签名并显示详细信息
        }
        
        if (!empty($this->examples)) {
            $out->line("\nExamples:");
            foreach ($this->examples as $example) {
                $out->line("  {$example['example']}");
                if (!empty($example['description'])) {
                    $out->line("    {$example['description']}");
                }
            }
        }
        
        if (!empty($this->related)) {
            $out->line("\nRelated commands:");
            foreach ($this->related as $command) {
                $out->line("  {$command}");
            }
        }
    }

    /**
     * 验证输入参数
     */
    protected function validateInput(Input $in, Output $out): bool
    {
        // 这里可以添加输入验证逻辑
        return true;
    }

    /**
     * 格式化输出错误信息
     */
    protected function error(Input $in, Output $out, string $message, int $code = 1): int
    {
        $out->error($message);
        return $code;
    }
}