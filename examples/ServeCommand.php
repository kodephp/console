<?php

declare(strict_types=1);

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例命令：启动 Web 服务器
 * 
 * 演示如何创建一个启动开发服务器的命令，
 * 支持指定应用目录、主机和端口。
 */
class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'serve', 
            '启动开发服务器', 
            'serve {app?} {--host:string=localhost} {--port:int=8080} {--secure:bool}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['server', 'start']);
        
        // 添加示例
        $this->example('serve', '在当前目录启动服务器');
        $this->example('serve ./public --host=0.0.0.0 --port=8000', '指定目录、主机和端口启动');
        $this->example('serve --secure', '启用 HTTPS 模式');
        
        // 设置相关命令
        $this->related(['hello', 'db:migrate']);
        
        // 设置命令组
        $this->group('development');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 获取参数和选项
        $app = $in->arg(0, getcwd());  // 默认为当前目录
        $host = $in->opt('host', 'localhost');
        $port = $in->cast($in->opt('port', 8080), 'int');
        $secure = $in->flag('secure');
        
        // 检查应用目录是否存在
        if (!is_dir($app)) {
            $out->error("应用目录 '{$app}' 不存在。");
            return 1;
        }
        
        // 输出启动信息
        $out->info("正在启动开发服务器...");
        $out->line("应用目录: {$app}");
        $out->line("主机: {$host}");
        $out->line("端口: {$port}");
        $out->line("安全模式: " . ($secure ? '是' : '否'));
        
        $protocol = $secure ? 'https' : 'http';
        $out->success("服务器已启动: {$protocol}://{$host}:{$port}");
        $out->line("按 Ctrl+C 停止服务器");
        
        // 实际启动服务器的代码
        // passthru("php -S {$host}:{$port} -t {$app}");
        
        return 0;
    }
}
