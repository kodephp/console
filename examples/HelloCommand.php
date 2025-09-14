<?php

declare(strict_types=1);

use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

/**
 * 示例命令：显示问候语
 * 
 * 这个命令演示了如何创建一个简单的命令，它接受一个可选的名称参数
 * 并支持 --uppercase 选项来将问候语转换为大写。
 */
class HelloCommand extends Command
{
    public function __construct()
    {
        // 设置命令名称
        $this->name = 'hello';
        
        // 设置命令描述
        $this->desc = 'Display a greeting message';
        
        // 设置命令用法
        $this->usage = 'hello {name?} {--uppercase}';
        
        // 注册命令签名
        $this->sig($this->usage);
    }

    /**
     * 执行命令
     * 
     * @param Input $in 输入对象
     * @param Output $out 输出对象
     * @return int 退出码
     */
    public function fire(Input $in, Output $out): int
    {
        // 获取 name 参数，默认为 'World'
        $name = $in->arg('name', 'World');
        
        // 构造问候语
        $greeting = "Hello, {$name}!";
        
        // 如果设置了 --uppercase 选项，则转换为大写
        if ($in->flag('uppercase')) {
            $greeting = strtoupper($greeting);
        }
        
        // 输出问候语
        $out->line($greeting);
        
        // 成功退出
        return 0;
    }
}