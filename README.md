# Kode Console: 健壮、通用的 PHP 控制台组件

> **包名称**: `kode/console`  
> **兼容性**: PHP 8.1+  
> **作者**: KodePHP 团队  
> **许可证**: MIT  
> **用途**: 为 `kodephp/framework` 提供底层 CLI 支持，同时支持任意 PHP 框架或独立使用  
> **设计原则**: 轻量、解耦、可扩展、无副作用、支持协变/逆变、反射安全、命名简洁无冲突

---

## 📦 概述

`kode/console` 是一个专为现代 PHP 应用设计的**通用控制台工具包**，旨在为 `kodephp` 框架提供底层 CLI 支持，同时确保其**完全独立于任何框架**，可被 Laravel、Symfony、Slim、thinkphp8、webman、thinkphp6、自研框架无缝集成。

本包采用 PHP 8.1+ 的最新特性（如 `readonly`、`enum`、`never`、`true` 返回类型等），支持泛型协变/逆变，使用反射进行元数据提取但保证性能与安全，提供完整的类型提示与代码智能感知支持。

---

## ✅ 核心特性

- ✅ **PHP 8.1+ 原生支持**
- ✅ **命名简洁无冲突**（避免与 PHP 原生函数/类重名）
- ✅ **支持协变 (covariance) 与逆变 (contravariance)**
- ✅ **反射安全封装**，防止注入与非法调用
- ✅ **无全局副作用**，不污染 `$_SERVER` 或 `define`
- ✅ **可作为依赖被任意框架调用**
- ✅ **IDE 完整识别支持**（通过 PHPStan 级别 9 + PHPDoc + Attributes）
- ✅ **支持未来扩展**：协程、多线程、多进程（通过事件钩子预留接口）

---

## 🧩 架构设计

```
kode/console
├── Command.php             // 命令基类
├── CommandExtended.php     // 增强版命令基类
├── Kernel.php              // 控制台内核，负责注册与调度
├── KernelExtended.php      // 增强版控制台内核
├── Input.php               // 输入解析器（argv）
├── Output.php              // 输出封装（支持颜色、格式）
├── OutputExtended.php      // 增强版输出封装
├── Signature.php           // 命令签名解析器（DSL 风格）
├── Event.php               // 事件系统
├── EventManager.php        // 事件管理器
├── CommandGroup.php        // 命令分组
├── InteractiveInput.php    // 交互式输入
├── Contract/               // 接口定义
│   ├── IsCommand.php
│   ├── IsInput.php
│   ├── IsOutput.php
│   ├── IsKernel.php
│   ├── IsEvent.php
│   ├── IsEventManager.php
│   └── IsMiddleware.php
├── Middleware/             // 中间件
│   └── LoggingMiddleware.php
├── Listener/               // 事件监听器
│   └── CommandLogger.php
└── Helper/                 // 工具类（反射、类型推断等）
    └── Reflector.php
```

---

## 🧱 核心类与方法（命名简洁、易记、无冲突）

### 1. `Command` 类

```php
namespace Kode\Console;

use Kode\Console\Contract\IsCommand;
use Kode\Console\Input;
use Kode\Console\Output;

abstract class Command implements IsCommand
{
    public readonly string $name;        // 命令名，如 "app:serve"
    public readonly string $desc;        // 描述
    public readonly string $usage;       // 用法说明

    /**
     * 执行命令
     */
    abstract public function fire(Input $in, Output $out): int;

    /**
     * 注册命令签名
     */
    public function sig(string $def): static { /* ... */ }

    /**
     * 设置描述
     */
    public function about(string $text): static { /* ... */ }
}
```

> 🔹 **命名理由**：`fire()` 比 `handle()` 更短且无 Laravel 冲突；`sig()` 是 `signature` 的极简缩写，易记。

### 2. `CommandExtended` 类（增强版）

```php
namespace Kode\Console;

abstract class CommandExtended extends Command
{
    /**
     * 设置命令别名
     */
    public function alias(string|array $alias): static;

    /**
     * 添加使用示例
     */
    public function example(string $example, string $description = ''): static;

    /**
     * 设置相关命令
     */
    public function related(string|array $commands): static;

    /**
     * 设置命令组
     */
    public function group(string $group): static;

    /**
     * 显示详细帮助信息
     */
    public function showHelp(Input $in, Output $out): void;
}
```

### 3. `Kernel` 控制台内核

```php
namespace Kode\Console;

class Kernel
{
    /**
     * @var Command[] 协变支持
     */
    private array $cmds = [];

    /**
     * 注册命令
     *
     * @param class-string<Command> $cls
     */
    public function add(string $cls): static;

    /**
     * 运行控制台
     */
    public function boot(array $argv): int;

    /**
     * 获取所有命令（逆变输入）
     *
     * @return iterable<Command>
     */
    public function all(): iterable;
}
```

### 4. `KernelExtended` 类（增强版）

```php
namespace Kode\Console;

class KernelExtended implements IsKernel
{
    /**
     * 添加命令别名
     */
    public function alias(string $alias, string $commandName): static;

    /**
     * 添加命令组
     */
    public function addGroup(CommandGroup $group): static;

    /**
     * 添加中间件
     */
    public function addMiddleware(IsMiddleware $middleware): static;

    /**
     * 设置事件管理器
     */
    public function setEventManager(IsEventManager $eventManager): static;
}
```

### 5. `Input` 输入解析器

```php
namespace Kode\Console;

class Input
{
    public function arg(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function flag(string $name): bool;     // --verbose
    public function opt(string $name): mixed;     // --port=8080
    public function raw(): array;                 // 原始 argv
}
```

> 🔹 方法名极简：`arg`, `flag`, `opt` —— 无歧义、易记、无 PHP 冲突

### 6. `Output` 输出封装

```php
namespace Kode\Console;

class Output
{
    public function line(string $text, string $color = ''): void;
    public function info(string $msg): void;
    public function warn(string $msg): void;
    public function error(string $msg): void;
    public function success(string $msg): void;
    public function raw(string $text): void;
}
```

### 7. `OutputExtended` 类（增强版）

```php
namespace Kode\Console;

class OutputExtended extends Output
{
    /**
     * 输出带样式的文本
     */
    public function styled(string $style, string $text): void;

    /**
     * 输出表格
     */
    public function table(array $headers, array $rows): void;

    /**
     * 输出进度条
     */
    public function progress(int $current, int $total, int $width = 50): void;

    /**
     * 输出JSON格式数据
     */
    public function json(mixed $data, int $flags = 0): void;
}
```

### 8. `Signature` 命令签名 DSL

```php
// 示例：serve --host=localhost --port=8080 {app?}
$sig = new Signature('serve {app?} {--host=} {--port=8080} {--secure}');

// 解析后生成元数据，用于 Input 验证
```

---

## 🧪 使用示例（任意框架均可调用）

```php
use Kode\Console\Command;
use Kode\Console\Kernel;
use Kode\Console\Input;
use Kode\Console\Output;

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

// 在任意框架中启动
$kernel = new Kernel();
$kernel->add(ServeCommand::class);
exit($kernel->boot($_SERVER['argv']));
```

### 使用增强功能示例

```php
use Kode\Console\CommandExtended;
use Kode\Console\KernelExtended;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\CommandGroup;
use Kode\Console\EventManager;
use Kode\Console\Event;

class DatabaseCommand extends CommandExtended
{
    public function __construct()
    {
        $this->name = 'db:migrate';
        $this->desc = 'Run database migrations';
        $this->usage = 'db:migrate {--force} {--path=}';
        
        // 添加别名
        $this->alias(['migrate', 'db:mig']);
        
        // 添加示例
        $this->example('db:migrate --force', 'Run migrations without confirmation');
        $this->example('db:migrate --path=/custom/path', 'Run migrations from custom path');
        
        // 设置相关命令
        $this->related(['db:seed', 'db:rollback']);
        
        // 设置命令组
        $this->group('database');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        $force = $in->flag('force');
        $path = $in->opt('path', 'migrations');
        
        if (!$force && !\Kode\Console\InteractiveInput::confirm('Run migrations?')) {
            $out->line('Migration cancelled.');
            return 0;
        }
        
        $out->info("Running migrations from {$path}...");
        // 迁移逻辑...
        
        $out->success('Migrations completed successfully!');
        return 0;
    }
}

// 使用增强内核
$kernel = new KernelExtended();

// 添加事件管理器
$eventManager = new EventManager();
$kernel->setEventManager($eventManager);

// 添加事件监听器
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 添加中间件
$kernel->addMiddleware(new LoggingMiddleware());

// 添加命令组
$databaseGroup = new CommandGroup('database', 'Database operations');
$databaseGroup->addCommand(new DatabaseCommand());
$kernel->addGroup($databaseGroup);

// 添加命令和别名
$kernel->add(DatabaseCommand::class);
$kernel->alias('migrate', 'db:migrate');

exit($kernel->boot($_SERVER['argv']));
```

---

## 🔒 安全与反射设计

```php
// Helper/Reflector.php
namespace Kode\Console\Helper;

class Reflector
{
    /**
     * 安全获取类的公共属性与方法
     * @template T of object
     * @param class-string<T> $cls
     * @return ReflectionClass<T>
     */
    public static function of(string $cls): \ReflectionClass
    {
        if (!class_exists($cls)) {
            throw new \InvalidArgumentException("Class {$cls} not found.");
        }

        $ref = new \ReflectionClass($cls);
        if (!$ref->isSubclassOf(Command::class)) {
            throw new \InvalidArgumentException("{$cls} must extend Command.");
        }

        return $ref;
    }
}
```

> ✅ 使用泛型模板 `@template T` 支持静态分析  
> ✅ 严格校验类存在性与继承关系  
> ✅ 防止非法反射调用

---

## 🧠 Trea IDE 识别支持

确保 **字节团队 Trea IDE** 完整识别类型、自动补全、跳转，本包提供：

### 1. 严格的 PHPStan 配置（`phpstan.neon`）

```neon
parameters:
    level: 9
    inferPrivatePropertyTypeFromConstructor: true
    checkGenericClassInNonGenericObjectType: true
```

### 2. 全量 PHPDoc 与 Attributes

```php
#[\Attribute]
class ConsoleCommand
{
    public function __construct(
        public string $name,
        public string $desc = ''
    ) {}
}

// 用于 IDE 识别
#[ConsoleCommand(name: 'app:serve', desc: 'Start server')]
class ServeCommand extends Command { ... }
```

### 3. `.phpstorm.meta.php` 支持（可选）

```php
// .phpstorm.meta.php
override(\Kode\Console\Kernel::add(0), map(['\::class' => \Kode\Console\Command::class]));
```

---

## 🚀 未来扩展性设计

| 特性         | 预留支持方式                     |
|--------------|----------------------------------|
| 协程         | `fire()` 返回 `Generator` 或 `Promise` |
| 多进程       | `Kernel` 提供 `fork()` 钩子       |
| 多线程       | 通过 `pthreads` 或 `parallel` 扩展（非内置，但不阻止） |
| 事件系统     | `Kernel` 触发 `before:run`, `after:exit` |

> ⚠️ 本包**不内置**协程/多线程，但**不阻碍**上层框架实现

---

## 📦 Composer 安装

```bash
composer require kode/console
```

## 🚀 快速开始

### 基础用法

1. 创建一个简单的命令：

```php
<?php

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

class HelloCommand extends Command
{
    public function __construct()
    {
        parent::__construct('hello', 'Say hello', 'hello {name?}');
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $name = $in->arg(1, 'World');
        $out->line("Hello, {$name}!");
        return 0;
    }
}
```

2. 创建控制台应用入口文件（console.php）：

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Kode\Console\Kernel;

// 创建内核实例
$kernel = new Kernel();

// 注册命令
$kernel->add(HelloCommand::class);

// 运行控制台应用
exit($kernel->boot($argv));
```

3. 运行命令：

```bash
php console.php hello
# 输出: Hello, World!

php console.php hello John
# 输出: Hello, John!
```

### 增强功能用法

1. 使用增强命令类和内核：

```php
<?php

use Kode\Console\CommandExtended;
use Kode\Console\KernelExtended;
use Kode\Console\Input;
use Kode\Console\OutputExtended;
use Kode\Console\Middleware\LoggingMiddleware;

class AdvancedCommand extends CommandExtended
{
    public function __construct()
    {
        $this->name = 'advanced';
        $this->desc = 'Advanced command with extended features';
        $this->usage = 'advanced {name} {--upper} {--repeat=1}';
        
        // 添加别名
        $this->alias(['adv', 'adv:cmd']);
        
        // 添加使用示例
        $this->example('advanced John --upper', 'Greet John in uppercase');
        $this->example('advanced Jane --repeat=3', 'Greet Jane 3 times');
        
        // 设置相关命令
        $this->related(['hello', 'goodbye']);
        
        // 设置命令组
        $this->group('demo');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        $name = $in->arg('name');
        $upper = $in->flag('upper');
        $repeat = (int) $in->opt('repeat', 1);
        
        $greeting = "Hello, {$name}!";
        if ($upper) {
            $greeting = strtoupper($greeting);
        }
        
        for ($i = 0; $i < $repeat; $i++) {
            $out->line($greeting);
        }
        
        return 0;
    }
}

// 使用增强内核
$kernel = new KernelExtended();

// 添加中间件
$kernel->addMiddleware(new LoggingMiddleware());

// 注册命令
$kernel->add(AdvancedCommand::class);

// 添加命令别名
$kernel->alias('adv', 'advanced');

exit($kernel->boot($argv));
```

### 交互式输入用法

```php
<?php

use Kode\Console\CommandExtended;
use Kode\Console\InteractiveInput;
use Kode\Console\Input;
use Kode\Console\Output;

class InteractiveCommand extends CommandExtended
{
    public function __construct()
    {
        $this->name = 'interactive';
        $this->desc = 'Interactive command example';
        $this->usage = 'interactive';
    }

    public function fire(Input $in, Output $out): int
    {
        // 询问用户输入
        $name = InteractiveInput::ask('What is your name?', 'Anonymous');
        
        // 确认操作
        if (!InteractiveInput::confirm("Hello, {$name}! Do you want to continue?", true)) {
            $out->line('Goodbye!');
            return 0;
        }
        
        // 选择选项
        $choices = [
            '1' => 'Option One',
            '2' => 'Option Two',
            '3' => 'Option Three'
        ];
        
        $selected = InteractiveInput::choice('Please select an option:', $choices, '1');
        $out->line("You selected: {$selected}");
        
        return 0;
    }
}
```

### 事件系统用法

```php
<?php

use Kode\Console\CommandExtended;
use Kode\Console\EventManager;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\Input;
use Kode\Console\Output;

class EventCommand extends CommandExtended
{
    public function __construct()
    {
        $this->name = 'event';
        $this->desc = 'Command with event system';
        $this->usage = 'event';
    }

    public function fire(Input $in, Output $out): int
    {
        $out->line('This command triggers events');
        return 0;
    }
}

// 设置事件管理器
$eventManager = new EventManager();
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 使用增强内核并设置事件管理器
$kernel = new KernelExtended();
$kernel->setEventManager($eventManager);
$kernel->add(EventCommand::class);

exit($kernel->boot($argv));
```

### 增强输出用法

```php
<?php

use Kode\Console\CommandExtended;
use Kode\Console\Input;
use Kode\Console\OutputExtended;

class OutputCommand extends CommandExtended
{
    public function __construct()
    {
        $this->name = 'output';
        $this->desc = 'Command with enhanced output';
        $this->usage = 'output';
    }

    public function fire(Input $in, Output $out): int
    {
        // 确保使用增强输出类
        if (!$out instanceof OutputExtended) {
            $out->error('This command requires OutputExtended');
            return 1;
        }
        
        // 带样式的文本输出
        $out->styled('success', 'This is a success message');
        $out->styled('error', 'This is an error message');
        $out->styled('warning', 'This is a warning message');
        $out->styled('info', 'This is an info message');
        
        // 表格输出
        $headers = ['Name', 'Age', 'City'];
        $rows = [
            ['John Doe', '30', 'New York'],
            ['Jane Smith', '25', 'Los Angeles'],
            ['Bob Johnson', '35', 'Chicago']
        ];
        $out->table($headers, $rows);
        
        // 进度条
        $total = 100;
        for ($i = 0; $i <= $total; $i++) {
            $out->progress($i, $total);
            usleep(50000); // 0.05秒延迟
        }
        $out->line(''); // 换行
        
        // JSON输出
        $data = [
            'name' => 'John Doe',
            'age' => 30,
            'city' => 'New York',
            'hobbies' => ['reading', 'swimming', 'coding']
        ];
        $out->json($data);
        
        return 0;
    }
}
```

---

## 📚 总结

| 项目             | 说明 |
|------------------|------|
| **包名**         | `kode/console` |
| **PHP 版本**     | 8.1+ |
| **命名风格**     | 简洁、无冲突（`fire`, `sig`, `in`, `out`） |
| **类型安全**     | 协变、逆变、泛型、`readonly` |
| **反射安全**     | 封装 `Reflector`，校验类合法性 |
| **IDE 支持**     | Trea / PHPStorm / VSCode 完整识别 |
| **框架兼容**     | Laravel、Symfony、自研框架均可集成 |
| **未来扩展**     | 不影响协程、多线程、多进程开发 |

---

> 🌟 **目标**：成为 PHP 社区最轻量、最健壮、最通用的控制台底层包，为 `kodephp` 及生态提供坚实基础。

--- 

📝 **文档版本**: `v1.1.0`  
📅 **最后更新**: `2025-09-13`

## 📦 版本发布

### GitHub Releases

要发布新版本到GitHub，请按照以下步骤操作：

1. 确保所有更改都已提交并推送到GitHub仓库
2. 在GitHub上创建新的Release：
   - 标签版本：`v1.1.0`
   - 发布标题：`Kode Console v1.1.0 - 增强功能版本`
   - 发布说明：
     ```
     ## Kode Console v1.1.0 发布说明

     ### 新增功能
     - 增强版命令系统（CommandExtended）
     - 事件系统（EventManager）
     - 中间件系统（Middleware）
     - 交互式输入支持（InteractiveInput）
     - 增强版输出功能（OutputExtended）

     ### 改进
     - 重构Kernel类为KernelExtended，支持更多高级功能
     - 完善README文档，添加增强功能使用示例
     - 更新composer.json，调整依赖和配置
     - 修复PHPStan代码质量检查问题，提升类型安全

     ### 使用示例
     请查看examples目录中的示例文件，了解如何使用新增的增强功能。
     ```

3. 上传编译后的.phar文件（如果有的话）
4. 发布版本