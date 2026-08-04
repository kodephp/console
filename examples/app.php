<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Kode\Console\Examples\HelloCommand;
use Kode\Console\Kernel;

// 最小可运行示例：三行代码跑起一个 CLI
exit((new Kernel('Hello App', '1.0.0'))
    ->add(HelloCommand::class)
    ->run($argv));
