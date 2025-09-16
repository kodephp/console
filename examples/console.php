<?php

require __DIR__ . '/../vendor/autoload.php';

use Kode\Console\Kernel;
use Kode\Console\EventManager;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\CommandGroup;

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
$kernel->add(\Kode\Console\Examples\ServeCommand::class);
$kernel->add(\Kode\Console\Examples\HelloCommand::class);
// DatabaseCommand通过命令组注册，不在这里直接注册

// 添加命令别名
$kernel->alias('migrate', 'db');
$kernel->alias('hi', 'hello');
$kernel->alias('greet', 'hello');

// 添加命令组
$databaseGroup = new CommandGroup('database', 'Database operations');
$databaseGroup->addCommand(new \Kode\Console\Examples\DatabaseCommand());
$kernel->addGroup($databaseGroup);

// 运行控制台应用
exit($kernel->boot($argv));