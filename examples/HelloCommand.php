<?php

declare(strict_types=1);

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例命令：输出问候语
 * 
 * 演示如何创建一个简单的命令，
 * 支持位置参数和布尔标志。
 */
class HelloCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'hello', 
            '输出问候语', 
            'hello {name?} {--uppercase:bool}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['hi', 'greet']);
        
        // 添加示例
        $this->example('hello', '输出默认问候语');
        $this->example('hello John', '向 John 问候');
        $this->example('hello John --uppercase', '向 John 问候（大写）');
        
        // 设置相关命令
        $this->related(['serve', 'db:migrate']);
        
        // 设置命令组
        $this->group('general');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 获取 name 参数，默认为 'World'
        $name = $in->arg(0, 'World');
        
        // 构造问候语
        $greeting = "你好, {$name}!";
        
        // 如果设置了 --uppercase 选项，则转换为大写
        if ($in->flag('uppercase')) {
            $greeting = strtoupper($greeting);
        }
        
        // 输出问候语
        $out->success($greeting);
        
        return 0;
    }
}
