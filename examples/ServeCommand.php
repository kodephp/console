<?php

declare(strict_types=1);

use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class ServeCommand extends Command
{
    public function __construct()
    {
        $this->name = 'serve';
        $this->desc = 'Start web server';
        $this->usage = 'serve {app?} {--host=localhost} {--port=8080}';
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $app = $in->arg('app', 'default');
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