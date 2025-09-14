<?php

declare(strict_types=1);

use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

/**
 * 示例命令：数据库操作命令
 * 
 * 这个命令演示了如何创建一个更复杂的命令，它支持多个选项和参数，
 * 包括带有默认值的选项和可选参数。
 */
class DatabaseCommand extends Command
{
    public function __construct()
    {
        // 设置命令名称
        $this->name = 'db';
        
        // 设置命令描述
        $this->desc = 'Database operations';
        
        // 设置命令用法
        $this->usage = 'db {operation} {table?} {--host=localhost} {--port=3306} {--database=} {--force}';
        
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
        // 获取操作参数
        $operation = $in->arg('operation');
        
        // 获取表名参数（可选）
        $table = $in->arg('table');
        
        // 获取选项
        $host = $in->opt('host');
        $port = $in->opt('port');
        $database = $in->opt('database');
        $force = $in->flag('force');
        
        // 显示操作信息
        $out->info("Database operation: {$operation}");
        
        if ($table) {
            $out->info("Table: {$table}");
        }
        
        $out->info("Host: {$host}:{$port}");
        
        if ($database) {
            $out->info("Database: {$database}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled!");
        }
        
        // 模拟执行操作
        switch ($operation) {
            case 'migrate':
                $out->success("Database migration completed successfully!");
                break;
                
            case 'seed':
                $out->success("Database seeding completed successfully!");
                break;
                
            case 'backup':
                $out->success("Database backup created successfully!");
                break;
                
            case 'restore':
                $out->success("Database restored successfully!");
                break;
                
            default:
                $out->error("Unknown operation: {$operation}");
                return 1;
        }
        
        // 成功退出
        return 0;
    }
}