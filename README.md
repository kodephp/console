# Kode Console: 健壮、通用的 PHP 控制台组件

> **包名称**: `kode/console`  
> **兼容性**: PHP 8.1+  
> **作者**: KodePHP 团队  
> **许可证**: Apache 2.0  
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
- ✅ **IDE 完整识别支持**（通过 PHPStan 级别 12 + PHPDoc + Attributes）
- ✅ **支持未来扩展**：协程、多线程、多进程（通过事件钩子预留接口）

---

## 🧩 架构设计

```
kode/console
├── Command.php             // 命令基类（包含所有增强功能）
├── Kernel.php              // 控制台内核，负责注册与调度（包含所有增强功能）
├── Input.php               // 输入解析器（argv）
├── Output.php              // 输出封装（支持颜色、格式）
├── Signature.php           // 命令签名解析器（DSL 风格）
├── Event.php               // 事件系统
├── EventManager.php        // 事件管理器
├── CommandGroup.php        // 命令分组
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
     * @var string[]
     */
    protected array $aliases = [];
    
    /**
     * @var array<array{example: string, description: string}>
     */
    protected array $examples = [];
    
    /**
     * @var string[]
     */
    protected array $related = [];
    
    protected ?string $group = null;

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

> 🔹 **命名理由**：`fire()` 比 `handle()` 更短且无 Laravel 冲突；`sig()` 是 `signature` 的极简缩写，易记。
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
     * @var array<string, CommandGroup>
     */
    private array $groups = [];
    
    /**
     * @var IsMiddleware[]
     */
    private array $middlewares = [];
    
    private ?IsEventManager $eventManager = null;
    
    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * 注册命令
     *
     * @param class-string<Command> $cls
     */
    public function add(string $cls): static;

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
    
    // 交互式输入功能
    public static function ask(string $question, string $default = ''): string;
    public static function confirm(string $question, bool $default = false): bool;
    public static function choice(string $question, array $choices, string|int|null $default = null): string|int|null;
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
    
    // 增强功能
    public function styled(string $text, string $style = 'info'): void;
    public function table(array $headers, array $rows): void;
    public function progress(int $current, int $total, int $width = 50): void;
    public function json(mixed $data): void;
}
```

> 🔹 颜色支持 ANSI 与自动检测 TTY

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
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\CommandGroup;
use Kode\Console\EventManager;
use Kode\Console\Event;

class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('serve', 'Start web server', 'serve {app?} {--host=localhost} {--port=8080}');
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['server', 'start']);
        
        // 添加示例
        $this->example('serve', 'Start server in current directory');
        $this->example('serve ./public --host=0.0.0.0 --port=8000', 'Start server with custom host and port');
        
        // 设置相关命令
        $this->related(['hello', 'db:migrate']);
        
        // 设置命令组
        $this->group('development');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 获取参数和选项
        $app = $in->arg(1, getcwd());  // 默认为当前目录
        $host = $in->opt('host', 'localhost');
        $port = $in->opt('port', 8080);
        
        // 检查应用目录是否存在
        if (!is_dir($app)) {
            $out->error("Application directory '{$app}' does not exist.");
            return 1;
        }
        
        // 输出启动信息
        $out->info("Starting development server...");
        $out->line("Application: {$app}");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        $out->success("Server started at http://{$host}:{$port}");
        $out->line("Press Ctrl+C to stop the server");
        
        // 这里可以添加实际启动服务器的逻辑
        // 例如：system("php -S {$host}:{$port} -t {$app}");
        
        // 成功退出
        return 0;
    }
}

class DatabaseCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'db', 
            'Database operations', 
            'db {operation} {table?} {--host=localhost} {--port=3306} {--database=} {--force}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['database', 'migrate']);
        
        // 添加示例
        $this->example('db migrate --database=myapp', 'Migrate the myapp database');
        $this->example('db seed users --force', 'Seed the users table with force mode');
        $this->example('db backup --host=prod.db.com --port=3307', 'Backup database from production server');
        
        // 设置相关命令
        $this->related(['serve', 'hello']);
        
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
        
        // 获取参数和选项
        $operation = $in->arg(1);
        $table = $in->arg(2);
        $host = $in->opt('host', 'localhost');
        $port = $in->opt('port', 3306);
        $database = $in->opt('database');
        $force = $in->flag('force');
        
        // 验证必需参数
        if (!$operation) {
            $out->error("Operation is required. Available operations: migrate, seed, backup, restore");
            return 1;
        }
        
        // 根据操作类型执行相应逻辑
        switch ($operation) {
            case 'migrate':
                return $this->migrate($out, $host, $port, $database, $table, $force);
                
            case 'seed':
                return $this->seed($out, $host, $port, $database, $table, $force);
                
            case 'backup':
                return $this->backup($out, $host, $port, $database, $table, $force);
                
            case 'restore':
                return $this->restore($out, $host, $port, $database, $table, $force);
                
            default:
                $out->error("Unknown operation '{$operation}'. Available operations: migrate, seed, backup, restore");
                return 1;
        }
    }
    
    /**
     * 执行迁移操作
     */
    private function migrate(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Migrating database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database migrated successfully!");
        return 0;
    }
    
    /**
     * 执行填充操作
     */
    private function seed(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Seeding database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database seeded successfully!");
        return 0;
    }
    
    /**
     * 执行备份操作
     */
    private function backup(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Backing up database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database backed up successfully!");
        return 0;
    }
    
    /**
     * 执行恢复操作
     */
    private function restore(Output $out, string $host, int $port, ?string $database, ?string $table, bool $force): int
    {
        $out->info("Restoring database...");
        $out->line("Host: {$host}");
        $out->line("Port: {$port}");
        
        if ($database) {
            $out->line("Database: {$database}");
        }
        
        if ($table) {
            $out->line("Table: {$table}");
        }
        
        if ($force) {
            $out->warn("Force mode enabled");
        }
        
        $out->success("Database restored successfully!");
        return 0;
    }
}

// 在任意框架中启动
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
```php
// 添加命令组
$databaseGroup = new CommandGroup('database', 'Database operations');
$kernel->addGroup($databaseGroup);

// 添加命令和别名
$kernel->add(ServeCommand::class);
$kernel->add(DatabaseCommand::class);
$kernel->alias('migrate', 'db:migrate');
$kernel->alias('s', 'serve');

exit($kernel->boot($argv));
```

## 🎯 交互式输入用法

```php
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;
use Kode\Console\InteractiveInput;

class CreateUserCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'user:create', 
            'Create a new user', 
            'user:create {name?} {email?} {--admin} {--password=} {--force}'
        );
        $this->sig($this->usage);
        
        // 添加示例
        $this->example('user:create', 'Create a user interactively');
        $this->example('user:create John john@example.com --admin', 'Create an admin user directly');
        
        // 设置相关命令
        $this->related(['user:delete', 'user:list']);
        
        // 设置命令组
        $this->group('user');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 获取参数和选项
        $name = $in->arg('name');
        $email = $in->arg('email');
        $isAdmin = $in->flag('admin');
        $password = $in->opt('password');
        $force = $in->flag('force');
        
        // 如果没有提供用户名，进行交互式输入
        if (!$name) {
            $name = Input::ask('Enter user name:');
            if (!$name) {
                $out->error('User name is required.');
                return 1;
            }
        }
        
        // 如果没有提供邮箱，进行交互式输入
        if (!$email) {
            $email = Input::ask('Enter email address:');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out->error('Invalid email address.');
                return 1;
            }
        }
        
        // 如果没有提供密码，进行交互式输入
        if (!$password) {
            $password = Input::ask('Enter password:');
            if (strlen($password) < 8) {
                $out->error('Password must be at least 8 characters.');
                return 1;
            }
            
            // 确认密码
            $confirmPassword = Input::ask('Confirm password:');
            if ($password !== $confirmPassword) {
                $out->error('Passwords do not match.');
                return 1;
            }
        }
        
        // 确认操作（除非使用了--force选项）
        if (!$force) {
            $role = $isAdmin ? 'administrator' : 'regular user';
            $confirm = Input::confirm("Create {$role} '{$name}' with email '{$email}'?");
            if (!$confirm) {
                $out->line('User creation cancelled.');
                return 0;
            }
        }
        
        // 创建用户逻辑（这里只是示例）
        $out->info("Creating user...");
        $out->line("Name: {$name}");
        $out->line("Email: {$email}");
        $out->line("Admin: " . ($isAdmin ? 'Yes' : 'No'));
        
        // 模拟创建过程
        sleep(1);
        
        $out->success("User '{$name}' created successfully!");
        return 0;
    }
}
```

## ⚡ 事件系统用法

```php
use Kode\Console\Command;
use Kode\Console\Kernel;
use Kode\Console\Input;
use Kode\Console\Output;
use Kode\Console\EventManager;
use Kode\Console\Event;

// 自定义事件监听器
class CommandExecutionLogger
{
    public function handle(Event $event): void
    {
        $command = $event->data['command'] ?? 'unknown';
        $time = date('Y-m-d H:i:s');
        
        // 记录命令执行日志
        file_put_contents(
            'command.log', 
            "[{$time}] Command executed: {$command}\n", 
            FILE_APPEND | LOCK_EX
        );
    }
}

class DatabaseBackupListener
{
    public function handle(Event $event): void
    {
        // 在数据库迁移前自动备份
        if ($event->name === 'db:before_migrate') {
            echo "Backing up database before migration...\n";
            // 执行备份逻辑
        }
    }
}

// 在应用启动时配置事件系统
$kernel = new Kernel();
$eventManager = new EventManager();

// 注册事件监听器
$eventManager->listen('command.executing', [new CommandExecutionLogger(), 'handle']);
$eventManager->listen('command.executed', [new CommandExecutionLogger(), 'handle']);
$eventManager->listen('db:before_migrate', [new DatabaseBackupListener(), 'handle']);

// 将事件管理器设置到内核
$kernel->setEventManager($eventManager);

// 在命令中触发自定义事件
class MigrateCommand extends Command
{
    public function __construct()
    {
        parent::__construct('db:migrate', 'Run database migrations', 'db:migrate {--force}');
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        // 触发迁移前事件
        $this->kernel->eventManager()->trigger('db:before_migrate', [
            'command' => $this->name
        ]);
        
        $out->info('Running database migrations...');
        
        // 执行迁移逻辑
        // ...
        
        $out->success('Migrations completed successfully!');
        
        // 触发迁移后事件
        $this->kernel->eventManager()->trigger('db:after_migrate', [
            'command' => $this->name
        ]);
        
        return 0;
    }
}
```

## 🌈 增强输出用法

```php
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;
use Kode\Console\OutputExtended;

class ReportCommand extends Command
{
    public function __construct()
    {
        parent::__construct('report', 'Generate system report', 'report {--format=text} {--output=}');
        $this->sig($this->usage);
        
        // 添加示例
        $this->example('report', 'Generate report in text format');
        $this->example('report --format=json', 'Generate report in JSON format');
        $this->example('report --format=table --output=report.txt', 'Generate table report and save to file');
        
        // 设置相关命令
        $this->related(['report:users', 'report:system']);
        
        // 设置命令组
        $this->group('utility');
    }

    public function fire(Input $in, Output $out): int
    {
        // 获取选项
        $format = $in->opt('format', 'text');
        $outputFile = $in->opt('output');
        
        // 系统信息数据
        $systemInfo = [
            'OS' => PHP_OS,
            'PHP Version' => PHP_VERSION,
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
        ];
        
        // 用户数据
        $users = [
            ['ID' => 1, 'Name' => 'John Doe', 'Email' => 'john@example.com', 'Role' => 'Admin'],
            ['ID' => 2, 'Name' => 'Jane Smith', 'Email' => 'jane@example.com', 'Role' => 'User'],
            ['ID' => 3, 'Name' => 'Bob Johnson', 'Email' => 'bob@example.com', 'Role' => 'User'],
        ];
        
        // 根据格式输出
        switch ($format) {
            case 'json':
                return $this->outputJson($out, $systemInfo, $users, $outputFile);
                
            case 'table':
                return $this->outputTable($out, $systemInfo, $users, $outputFile);
                
            default:
                return $this->outputText($out, $systemInfo, $users, $outputFile);
        }
    }
    
    private function outputText(Output $out, array $systemInfo, array $users, ?string $outputFile): int
    {
        $content = "=== System Report ===\n\n";
        
        // 输出系统信息
        $content .= "System Information:\n";
        foreach ($systemInfo as $key => $value) {
            $content .= "  {$key}: {$value}\n";
        }
        
        $content .= "\nUser List:\n";
        foreach ($users as $user) {
            $content .= "  - {$user['Name']} ({$user['Email']}) [{$user['Role']}]\n";
        }
        
        // 如果指定了输出文件，则写入文件
        if ($outputFile) {
            file_put_contents($outputFile, $content);
            $out->success("Report saved to {$outputFile}");
        } else {
            // 否则直接输出到控制台
            $out->raw($content);
        }
        
        return 0;
    }
    
    private function outputJson(Output $out, array $systemInfo, array $users, ?string $outputFile): int
    {
        $data = [
            'system' => $systemInfo,
            'users' => $users,
            'generated_at' => date('c')
        ];
        
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        // 如果指定了输出文件，则写入文件
        if ($outputFile) {
            file_put_contents($outputFile, $json);
            $out->success("JSON report saved to {$outputFile}");
        } else {
            // 否则直接输出到控制台
            $out->json($data);
        }
        
        return 0;
    }
    
    private function outputTable(Output $out, array $systemInfo, array $users, ?string $outputFile): int
    {
        // 输出系统信息表格
        $out->info("System Information:");
        $out->table(array_keys($systemInfo), [array_values($systemInfo)]);
        
        $out->line(""); // 空行
        
        // 输出用户表格
        $out->info("User List:");
        $out->table(['ID', 'Name', 'Email', 'Role'], $users);
        
        // 如果指定了输出文件，提示保存
        if ($outputFile) {
            $out->warn("Note: Table format can only be displayed in console. To save, use --format=text or --format=json");
        }
        
        return 0;
    }
}
```

## 🚀 使用增强内核示例

```php
use Kode\Console\Kernel;
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Listener\CommandLogger;
use Kode\Console\CommandGroup;
use Kode\Console\EventManager;
use Kode\Console\Event;

// 自定义中间件示例
class TimingMiddleware
{
    public function handle(Input $in, Output $out, callable $next): int
    {
        $start = microtime(true);
        $result = $next($in, $out);
        $end = microtime(true);
        
        $duration = round(($end - $start) * 1000, 2);
        $out->line("\n<fg=gray>Execution time: {$duration}ms</>");
        
        return $result;
    }
}

// 自定义命令组示例
class DevelopmentGroup extends CommandGroup
{
    public function __construct()
    {
        parent::__construct('dev', 'Development tools');
        
        // 可以在这里预定义一些开发相关的命令
        // $this->addCommand(new ServeCommand());
        // $this->addCommand(new TestCommand());
    }
}

// 扩展内核示例
class ApplicationKernel extends Kernel
{
    protected array $config;
    
    public function __construct(array $config = [])
    {
        parent::__construct();
        $this->config = $config;
    }
    
    /**
     * 自定义启动逻辑
     */
    public function boot(array $argv): int
    {
        // 在启动前执行一些初始化逻辑
        $this->initialize();
        
        // 调用父类的启动逻辑
        return parent::boot($argv);
    }
    
    /**
     * 初始化应用
     */
    protected function initialize(): void
    {
        // 设置时区
        if (isset($this->config['timezone'])) {
            date_default_timezone_set($this->config['timezone']);
        }
        
        // 注册全局异常处理器
        set_exception_handler([$this, 'handleException']);
    }
    
    /**
     * 全局异常处理
     */
    public function handleException(Throwable $exception): void
    {
        $this->output->error("Error: " . $exception->getMessage());
        $this->output->line("File: " . $exception->getFile() . ":" . $exception->getLine());
        
        // 记录详细错误日志
        error_log($exception->__toString());
    }
    
    /**
     * 获取配置值
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}

// 使用增强内核的示例应用
class AppCommand extends Command
{
    public function __construct()
    {
        parent::__construct('app:info', 'Show application information', 'app:info');
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        // 获取内核实例
        $kernel = $this->kernel;
        
        // 检查是否为扩展内核
        if ($kernel instanceof ApplicationKernel) {
            $out->info("Application Information:");
            $out->line("Kernel: " . get_class($kernel));
            $out->line("Timezone: " . date_default_timezone_get());
            $out->line("Config items: " . count($kernel->getConfig('app', [])));
        } else {
            $out->info("Standard Kernel Information:");
            $out->line("Kernel: " . get_class($kernel));
        }
        
        // 显示所有已注册的命令
        $out->line("\nRegistered Commands:");
        foreach ($kernel->all() as $command) {
            $out->line("  - {$command->name}: {$command->desc}");
        }
        
        return 0;
    }
}

// 应用启动脚本
$config = [
    'timezone' => 'Asia/Shanghai',
    'app' => [
        'name' => 'My Application',
        'version' => '1.0.0'
    ]
];

// 创建增强内核实例
$kernel = new ApplicationKernel($config);

// 设置事件管理器
$eventManager = new EventManager();
$kernel->setEventManager($eventManager);

// 添加事件监听器
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 添加中间件
$kernel->addMiddleware(new LoggingMiddleware());
$kernel->addMiddleware(new TimingMiddleware());

// 创建命令组
$devGroup = new DevelopmentGroup();
$kernel->addGroup($devGroup);

// 注册命令
$kernel->add(AppCommand::class);

// 启动应用
exit($kernel->boot($argv));
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

## 🧠 IDE 识别支持

确保 **IDE** 完整识别类型、自动补全、跳转，本包提供：

### 1. 严格的 PHPStan 配置（`phpstan.neon`）

```neon
parameters:
    level: 12
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

### 综合使用示例

下面是一个综合性的使用示例，展示了如何创建一个功能完整的控制台应用，包含了命令定义、事件系统、中间件、交互式输入和增强输出等功能：

1. 创建一个功能丰富的命令：

```php
<?php

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

class DemoCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'demo', 
            'A demo command showcasing various features', 
            'demo {name?} {--upper} {--repeat=1} {--interactive}'
        );
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['d', 'demo:run']);
        
        // 添加使用示例
        $this->example('demo John --upper', 'Greet John in uppercase');
        $this->example('demo Jane --repeat=3', 'Greet Jane 3 times');
        $this->example('demo --interactive', 'Run in interactive mode');
        
        // 设置相关命令
        $this->related(['hello', 'serve']);
        
        // 设置命令组
        $this->group('examples');
    }

    public function fire(Input $in, Output $out): int
    {
        // 显示帮助信息
        if ($in->flag('help')) {
            $this->showHelp($in, $out);
            return 0;
        }
        
        // 交互式模式
        if ($in->flag('interactive')) {
            return $this->runInteractive($in, $out);
        }
        
        // 获取参数和选项
        $name = $in->arg('name', 'World');
        $upper = $in->flag('upper');
        $repeat = (int) $in->opt('repeat', 1);
        
        // 处理问候语
        $greeting = "Hello, {$name}!";
        if ($upper) {
            $greeting = strtoupper($greeting);
        }
        
        // 输出问候语
        for ($i = 0; $i < $repeat; $i++) {
            $out->line($greeting);
        }
        
        // 展示增强输出功能
        $out->success("Command executed successfully!");
        
        // 表格输出示例
        $out->info("User Information:");
        $headers = ['Name', 'Role', 'Status'];
        $rows = [
            [$name, 'User', 'Active'],
            ['System', 'Admin', 'Online']
        ];
        $out->table($headers, $rows);
        
        // JSON输出示例
        $data = [
            'command' => 'demo',
            'name' => $name,
            'upper' => $upper,
            'repeat' => $repeat
        ];
        $out->json($data);
        
        return 0;
    }
    
    private function runInteractive(Input $in, Output $out): int
    {
        // 询问用户输入
        $name = $in->ask('What is your name?', 'Anonymous');
        
        // 确认操作
        if (!$in->confirm("Hello, {$name}! Do you want to continue?", true)) {
            $out->line('Goodbye!');
            return 0;
        }
        
        // 选择选项
        $choices = [
            '1' => 'Basic greeting',
            '2' => 'Uppercase greeting',
            '3' => 'Repeat greeting'
        ];
        
        $selected = $in->choice('Please select a greeting style:', $choices, '1');
        
        switch ($selected) {
            case '1':
                $out->line("Hello, {$name}!");
                break;
            case '2':
                $out->line("HELLO, {$name}!");
                break;
            case '3':
                $repeat = (int) $in->ask('How many times should I repeat?', '3');
                for ($i = 0; $i < $repeat; $i++) {
                    $out->line("Hello, {$name}!");
                }
                break;
        }
        
        return 0;
    }
}
```

2. 创建控制台应用入口文件（console.php）：

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Kode\Console\Kernel;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\EventManager;
use Kode\Console\Listener\CommandLogger;

// 创建内核实例
$kernel = new Kernel();

// 设置事件管理器
$eventManager = new EventManager();
$kernel->setEventManager($eventManager);

// 添加事件监听器
$logger = new CommandLogger();
$eventManager->listen('command.executing', [$logger, 'handle']);
$eventManager->listen('command.executed', [$logger, 'handle']);

// 添加中间件
$kernel->addMiddleware(new LoggingMiddleware());

// 注册命令
$kernel->add(DemoCommand::class);

// 添加命令别名
$kernel->alias('d', 'demo');

// 运行控制台应用
exit($kernel->boot($argv));
```

3. 运行命令：

```bash
# 基础用法
php console.php demo
# 输出: Hello, World!

# 带参数
php console.php demo John
# 输出: Hello, John!

# 使用选项
php console.php demo Jane --upper --repeat=3
# 输出: HELLO, Jane! (重复3次)

# 交互式模式
php console.php demo --interactive

# 查看帮助
php console.php demo --help
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

📝 **文档版本**: `v1.2.0`  
📅 **最后更新**: `2025-09-17`

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
     - 命令系统增强（支持别名、示例、相关命令和命令组）
     - 事件系统（EventManager）
     - 中间件系统（Middleware）
     - 交互式输入支持（Input）
     - 增强版输出功能（Output）

     ### 改进
     - 完善README文档，添加增强功能使用示例
     - 更新composer.json，调整依赖和配置
     - 修复PHPStan代码质量检查问题，提升类型安全

     ### 使用示例
     请查看examples目录中的示例文件，了解如何使用新增的增强功能。
     ```

3. 上传编译后的.phar文件（如果有的话）
4. 发布版本