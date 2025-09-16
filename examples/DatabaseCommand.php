<?php

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例命令：数据库操作
 * 
 * 这个命令演示了如何创建一个处理多种操作的命令，
 * 它支持迁移、填充、备份和恢复数据库。
 */
class DatabaseCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'db', 
            'Database operations', 
            'db {operation} {table?} {--host=localhost} {--port=3306} {--database=} {--force}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['database', 'migrate']);
        
        // 添加示例
        $this->example('db migrate --database=myapp', 'Migrate the myapp database');
        $this->example('db seed users --force', 'Seed the users table with force mode');
        $this->example('db backup --host=prod.db.com --port=3307', 'Backup database from production server');
        
        // 设置相关命令
        $this->related(['serve', 'hello']);
        
        // 设置命令组
        $this->group('database');
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
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 获取参数和选项
        $operation = $in->arg(0);
        $table = $in->arg(1);
        $host = $in->opt('host', 'localhost') ?? 'localhost';
        $port = $in->opt('port', 3306) ?? 3306;
        $database = $in->opt('database');
        $force = $in->flag('force');
        
        // 验证必需参数
        if (!$operation) {
            $out->error("Operation is required. Available operations: migrate, seed, backup, restore");
            return 1;
        }
        
        // 根据操作类型执行相应逻辑
        switch ($operation) {
            case 'migrate':
                return $this->migrate($out, $host, $port, $database, $table, $force);
                
            case 'seed':
                return $this->seed($out, $host, $port, $database, $table, $force);
                
            case 'backup':
                return $this->backup($out, $host, $port, $database, $table, $force);
                
            case 'restore':
                return $this->restore($out, $host, $port, $database, $table, $force);
                
            default:
                $out->error("Unknown operation '{$operation}'. Available operations: migrate, seed, backup, restore");
                return 1;
        }
    }
    
    /**
     * 执行迁移操作
     */
    private function migrate(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Migrating database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database migrated successfully!");
        return 0;
    }
    
    /**
     * 执行填充操作
     */
    private function seed(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Seeding database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database seeded successfully!");
        return 0;
    }
    
    /**
     * 执行备份操作
     */
    private function backup(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Backing up database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database backed up successfully!");
        return 0;
    }
    
    /**
     * 执行恢复操作
     */
    private function restore(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Restoring database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database restored successfully!");
        return 0;
    }
}