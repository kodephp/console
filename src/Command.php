<?php

declare(strict_types=1);

namespace Nova\Console;

use Nova\Console\Contract\IsCommand;

abstract class Command implements IsCommand
{
    public function __construct(
        public readonly string $name = '',        // 命令名，如 "app:serve"
        public readonly string $desc = '',        // 描述
        public readonly string $usage = ''       // 用法说明
    ) {
    }

    /**
     * 设置命令名称
     */
    protected function setName(string $name): static
    {
        // 使用反射绕过readonly限制来设置属性值
        $reflection = new \ReflectionClass($this);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($this, $name);
        return $this;
    }

    /**
     * 设置命令描述
     */
    protected function setDesc(string $desc): static
    {
        // 使用反射绕过readonly限制来设置属性值
        $reflection = new \ReflectionClass($this);
        $descProperty = $reflection->getProperty('desc');
        $descProperty->setAccessible(true);
        $descProperty->setValue($this, $desc);
        return $this;
    }

    /**
     * 设置命令用法
     */
    protected function setUsage(string $usage): static
    {
        // 使用反射绕过readonly限制来设置属性值
        $reflection = new \ReflectionClass($this);
        $usageProperty = $reflection->getProperty('usage');
        $usageProperty->setAccessible(true);
        $usageProperty->setValue($this, $usage);
        return $this;
    }

    /**
     * 执行命令
     */
    abstract public function fire(Input $in, Output $out): int;

    /**
     * 注册命令签名
     */
    public function sig(string $def): static
    {
        $this->setUsage($def);
        // 签名解析逻辑将在 Signature 类中实现
        return $this;
    }

    /**
     * 设置描述
     */
    public function about(string $text): static
    {
        return $this->setDesc($text);
    }
}