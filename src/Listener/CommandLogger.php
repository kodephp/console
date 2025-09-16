<?php

namespace Kode\Console\Listener;

use Kode\Console\Contract\IsEvent;

class CommandLogger
{
    public function handle(IsEvent $event): void
    {
        $logMessage = sprintf(
            "[%s] Event: %s, Data: %s\n",
            date('Y-m-d H:i:s'),
            $event->getName(),
            json_encode($event->getData())
        );
        
        // 确保runtime目录存在
        $runtimeDir = 'runtime/logs';
        if (!is_dir($runtimeDir)) {
            mkdir($runtimeDir, 0755, true);
        }
        
        // 写入日志文件到runtime目录
        file_put_contents($runtimeDir . '/command.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}