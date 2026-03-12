<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Kode\Console\Kernel;
use Kode\Console\EventManager;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\CommandGroup;
use Kode\Console\Examples\ServeCommand;
use Kode\Console\Examples\HelloCommand;
use Kode\Console\Examples\DatabaseCommand;

// 创建内核实例
$kernel = new Kernel();

// 添加事件管理器（可选）
$eventManager = new EventManager();
$kernel->setEventManager($eventManager);

// 添加事件监听器
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 添加中间件（可选）
$kernel->addMiddleware(new LoggingMiddleware());

// 注册命令
$kernel->add(HelloCommand::class);
$kernel->add(ServeCommand::class);

// 添加命令别名
$kernel->alias('hi', 'hello');
$kernel->alias('server', 'serve');

// 创建命令组
$databaseGroup = new CommandGroup('database', '数据库操作');
$databaseGroup->addCommand(new DatabaseCommand());
$kernel->addGroup($databaseGroup);

// 运行控制台应用
exit($kernel->boot($argv));
