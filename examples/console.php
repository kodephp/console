<?php

require __DIR__ . '/../vendor/autoload.php';

use Kode\Console\Kernel;

// 创建内核实例
$kernel = new Kernel();

// 注册命令
$kernel->add(\Kode\Console\Examples\ServeCommand::class);
$kernel->add(\Kode\Console\Examples\HelloCommand::class);
$kernel->add(\Kode\Console\Examples\DatabaseCommand::class);

// 运行控制台应用
exit($kernel->boot($argv));