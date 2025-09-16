<?php

use Kode\Console\Kernel;
use Kode\Console\EventManager;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Examples\HelloCommand;

// 创建内核实例
$kernel = new Kernel();

// 添加事件管理器
$eventManager = new EventManager();
$kernel->setEventManager($eventManager);

// 添加事件监听器
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 添加中间件
$kernel->addMiddleware(new LoggingMiddleware());

// 注册命令
$kernel->add(HelloCommand::class);

// 运行控制台应用
exit($kernel->boot($argv));