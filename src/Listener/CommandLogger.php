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
        
        // 这里可以写入日志文件
        file_put_contents('command.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}