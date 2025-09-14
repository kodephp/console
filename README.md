# Nova Console

健壮、通用的 PHP 控制台组件

[![PHP Version](https://img.shields.io/badge/php-^8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)](LICENSE)

## 简介

`nova/console` 是一个专为现代 PHP 应用设计的通用控制台工具包，旨在为 `novaphp` 框架提供底层 CLI 支持，同时确保其完全独立于任何框架，可被任意 PHP 框架无缝集成。

## 特性

- ✅ **PHP 8.1+ 原生支持**
- ✅ **命名简洁无冲突**
- ✅ **支持协变与逆变**
- ✅ **反射安全封装**
- ✅ **无全局副作用**
- ✅ **IDE 完整识别支持**
- ✅ **可扩展设计**
- ✅ **便捷快速的命令扩展**

## 安装

```bash
composer require nova/console
```

## 使用

### 创建命令

```php
use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class ServeCommand extends Command
{
    public function __construct()
    {
        $this->name = 'serve';
        $this->desc = 'Start web server';
        $this->usage = 'serve {app} {--port=8080}';
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $app = $in->arg('app', 'default');
        $port = $in->opt('port');

        $out->info("Starting server for {$app} on :{$port}");
        // 启动逻辑...

        return 0; // 成功退出
    }
}
```

### 运行命令

```php
use Nova\Console\Kernel;

$kernel = new Kernel();
$kernel->add(ServeCommand::class);
exit($kernel->boot($_SERVER['argv']));
```

### 扩展命令示例

#### 数据库迁移命令

```php
use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class MigrateCommand extends Command
{
    public function __construct()
    {
        $this->name = 'migrate';
        $this->desc = 'Run database migrations';
        $this->usage = 'migrate {--fresh} {--seed}';
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        if ($in->flag('fresh')) {
            $out->info('Dropping all tables...');
        }

        $out->info('Running migrations...');
        
        if ($in->flag('seed')) {
            $out->info('Seeding database...');
        }

        $out->success('Migrations completed successfully!');
        return 0;
    }
}
```

#### 缓存清理命令

```php
use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class CacheClearCommand extends Command
{
    public function __construct()
    {
        $this->name = 'cache:clear';
        $this->desc = 'Clear application cache';
        $this->usage = 'cache:clear {store?} {--tags=*}';
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $store = $in->arg('store', 'default');
        $tags = $in->opt('tags');

        $out->info("Clearing cache for store: {$store}");
        
        if ($tags) {
            $out->info("Clearing tagged cache: {$tags}");
        }

        $out->success('Cache cleared successfully!');
        return 0;
    }
}
```

### 注册和运行多个命令

```php
use Nova\Console\Kernel;

$kernel = new Kernel();
$kernel->add(ServeCommand::class);
$kernel->add(MigrateCommand::class);
$kernel->add(CacheClearCommand::class);

exit($kernel->boot($_SERVER['argv']));
```

### 命令签名语法

Nova Console 使用简洁的 DSL 来定义命令签名：

- `argument` - 必需参数
- `argument?` - 可选参数
- `argument=default` - 带默认值的参数
- `{--option}` - 布尔选项
- `{--option=}` - 带值的选项
- `{--option=default}` - 带默认值的选项

示例：
```php
// 复杂命令签名示例
$this->sig('user:create {name} {email} {--admin} {--role=member} {--permissions=*}');
```

### 更多示例

在 `examples/` 目录中，你可以找到更多示例命令：

1. `HelloCommand.php` - 一个简单的问候命令
2. `DatabaseCommand.php` - 一个演示复杂参数和选项的数据库操作命令
3. `ServeCommand.php` - 一个Web服务器启动命令
4. `console.php` - 一个演示如何注册和运行多个命令的完整示例

你可以通过以下方式运行这些示例：

```bash
cd examples
php console.php hello
php console.php hello John
php console.php hello John --uppercase
php console.php db migrate --database=myapp --force
```

## 许可证

Apache License 2.0