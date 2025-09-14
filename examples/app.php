<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nova\Console\Kernel;

// 注册命令
$kernel = new Kernel();
$kernel->add(ServeCommand::class);

// 运行应用
exit($kernel->boot($argv));