<?php

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例命令：启动Web服务器
 * 
 * 这个命令演示了如何创建一个启动Web服务器的命令，
 * 它支持指定应用目录、主机和端口。
 */
class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'serve', 
            'Start web server', 
            'serve {app?} {--host=localhost} {--port=8080}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['server', 'start']);
        
        // 添加示例
        $this->example('serve', 'Start server in current directory');
        $this->example('serve ./public --host=0.0.0.0 --port=8000', 'Start server with custom host and port');
        
        // 设置相关命令
        $this->related(['hello', 'db:migrate']);
        
        // 设置命令组
        $this->group('development');
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
        $app = $in->arg(1, getcwd());  // 默认为当前目录
        $host = $in->opt('host', 'localhost');
        $port = $in->opt('port', 8080);
        
        // 检查应用目录是否存在
        if (!is_dir($app)) {
            $out->error("Application directory '{$app}' does not exist.");
            return 1;
        }
        
        // 输出启动信息
        $out->info("Starting development server...");
        $out->line("Application: {$app}");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        $out->success("Server started at http://{$host}:{$port}");
        $out->line("Press Ctrl+C to stop the server");
        
        // 这里可以添加实际启动服务器的逻辑
        // 例如：system("php -S {$host}:{$port} -t {$app}");
        
        // 成功退出
        return 0;
    }
}