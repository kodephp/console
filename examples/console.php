<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Kode\Console\CommandGroup;
use Kode\Console\Examples\DatabaseCommand;
use Kode\Console\Examples\HelloCommand;
use Kode\Console\Examples\ServeCommand;
use Kode\Console\EventManager;
use Kode\Console\Kernel;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Middleware\LoggingMiddleware;

$events = new EventManager();
$events->listen('command.*', (new CommandLogger(__DIR__ . '/../runtime/logs/command.log'))->handle(...));

$kernel = new Kernel('Kode Console Demo', Kernel::VERSION);

$kernel->setEventManager($events)
    ->addMiddleware(new LoggingMiddleware())
    ->addMany([HelloCommand::class, ServeCommand::class])
    ->addGroup((new CommandGroup('database', '数据库操作'))->addCommand(new DatabaseCommand()));

exit($kernel->run($argv));
