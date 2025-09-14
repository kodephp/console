<?php

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('serve', 'Start web server', 'serve {app?} {--host=localhost} {--port=8080}');
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $app = $in->arg(1, 'default');
        $host = $in->opt('host') ?? 'localhost';
        $port = $in->opt('port') ?? '8080';

        $out->info("Starting server for {$app} on {$host}:{$port}");
        $out->line("This is a demo command. In a real application, this would start a web server.");
        
        // 模拟服务器启动过程
        for ($i = 1; $i <= 3; $i++) {
            $out->line("Starting server step {$i}...");
            usleep(500000); // 0.5秒延迟
        }
        
        $out->success("Server started successfully!");
        $out->line("URL: http://{$host}:{$port}");

        return 0; // 成功退出
    }
}