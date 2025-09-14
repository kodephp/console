<?php

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

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
        parent::__construct('hello', 'Display a greeting message', 'hello {name?} {--uppercase}');
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
        // 参数索引从0开始，第0个参数是命令名，第1个参数是第一个实际参数
        $name = $in->arg(1, 'World');
        
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