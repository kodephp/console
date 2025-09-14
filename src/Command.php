<?php

namespace Kode\Console;

use Kode\Console\Contract\IsCommand;

abstract class Command implements IsCommand
{
    public readonly string $name;        // 命令名，如 "app:serve"
    public readonly string $desc;        // 描述
    public readonly string $usage;       // 用法说明

    public function __construct(
        string $name = '',        // 命令名，如 "app:serve"
        string $desc = '',        // 描述
        string $usage = ''       // 用法说明
    ) {
        // 在PHP 8.1+中，readonly属性只能在构造函数中初始化一次
        $this->name = $name;
        $this->desc = $desc;
        $this->usage = $usage;
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
        // 由于readonly属性不能被修改，我们不执行任何操作
        // 签名解析逻辑将在 Signature 类中实现
        return $this;
    }

    /**
     * 设置描述
     */
    public function about(string $text): static
    {
        // 由于readonly属性不能被修改，我们不执行任何操作
        return $this;
    }
}