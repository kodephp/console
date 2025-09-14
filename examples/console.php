#!/usr/bin/env php
<?php

declare(strict_types=1);

// 引入 Composer 自动加载器
require __DIR__ . '/../vendor/autoload.php';

use Nova\Console\Kernel;

// 创建控制台内核
$kernel = new Kernel();

// 注册命令
// 这里我们注册了三个示例命令：
// 1. ServeCommand - 来自 examples/ServeCommand.php
// 2. HelloCommand - 来自 examples/HelloCommand.php
// 3. DatabaseCommand - 来自 examples/DatabaseCommand.php

// 首先需要包含命令文件
require_once __DIR__ . '/ServeCommand.php';
require_once __DIR__ . '/HelloCommand.php';
require_once __DIR__ . '/DatabaseCommand.php';

// 注册命令类
$kernel->add(ServeCommand::class);
$kernel->add(HelloCommand::class);
$kernel->add(DatabaseCommand::class);

// 运行控制台应用
// 传递命令行参数给 boot 方法
exit($kernel->boot($argv));