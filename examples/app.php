<?php

use Kode\Console\Kernel;

// 注册命令
$kernel = new Kernel();
$kernel->add(ServeCommand::class);

// 运行应用
exit($kernel->boot($argv));