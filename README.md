# Kode Console

> **健壮、通用的 PHP 控制台组件**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-Apache%202.0-green.svg)](LICENSE)

## 📦 简介

`kode/console` 是一个专为现代 PHP 应用设计的**通用控制台工具包**，采用 PHP 8.1+ 最新特性，提供轻量、解耦、可扩展的命令行开发体验。

### ✨ 特性

- ✅ **PHP 8.1+ 原生支持** - 使用 `readonly`、`match`、命名参数等现代特性
- ✅ **命名简洁无冲突** - 避免与 PHP 原生函数/类重名
- ✅ **类型安全** - 支持协变、逆变、泛型、`readonly`
- ✅ **自定义参数扩展** - 支持参数类型、验证和默认值
- ✅ **智能参数解析** - 自动类型转换和验证
- ✅ **框架无关** - 可被 Laravel、Symfony、ThinkPHP 等任意框架集成
- ✅ **IDE 完整支持** - 通过 PHPStan 级别 9 + PHPDoc

## 📦 安装

```bash
composer require kode/console
```

## 🚀 快速开始

### 创建命令

```php
<?php

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

class HelloCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'hello',                           // 命令名
            '输出问候语',                       // 描述
            'hello {name?} {--upper:bool}'    // 签名
        );
        
        $this->sig($this->usage);
    }

    public function fire(Input $in, Output $out): int
    {
        $name = $in->arg(0, 'World');
        $upper = $in->flag('upper');
        
        $greeting = "Hello, {$name}!";
        
        if ($upper) {
            $greeting = strtoupper($greeting);
        }
        
        $out->success($greeting);
        
        return 0; // 返回 0 表示成功
    }
}
```

### 运行命令

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Kode\Console\Kernel;

$kernel = new Kernel();
$kernel->add(HelloCommand::class);

exit($kernel->boot($argv));
```

```bash
php console.php hello John
# 输出: Hello, John!

php console.php hello --upper
# 输出: HELLO, WORLD!
```

## 📖 详细文档

### 命令签名 DSL

使用签名 DSL 定义命令的参数和选项：

```
command {arg1} {arg2?} {--option=default} {--flag:bool}
```

#### 参数定义

| 格式 | 说明 |
|------|------|
| `{name}` | 必填参数 |
| `{name?}` | 可选参数 |
| `{name=default}` | 带默认值的参数 |
| `{name:string}` | 带类型的参数 |
| `{name:int=100}` | 带类型和默认值的参数 |

#### 选项定义

| 格式 | 说明 |
|------|------|
| `{--flag}` | 布尔标志 |
| `{--option=}` | 必填值选项 |
| `{--option=default}` | 带默认值的选项 |
| `{--option:int}` | 带类型的选项 |
| `{--option\|-o}` | 带短别名的选项 |

#### 支持的类型

- `string` - 字符串（默认）
- `int` / `integer` - 整数
- `float` / `double` - 浮点数
- `bool` / `boolean` - 布尔值
- `array` - 数组（逗号分隔）

### 输入处理

```php
// 获取位置参数
$name = $in->arg(0, 'default');

// 获取选项值
$port = $in->opt('port');

// 检查标志
$verbose = $in->flag('v');

// 类型转换
$port = $in->cast($in->opt('port'), 'int');

// 参数验证
$valid = $in->validate('port', $port, ['required', 'numeric']);
```

### 交互式输入

```php
// 询问用户输入
$name = Input::ask('请输入名称', '默认名称');

// 确认操作
if (Input::confirm('确定要删除吗？', false)) {
    // 执行删除
}

// 选择选项
$choice = Input::choice('请选择环境', [
    'dev' => '开发环境',
    'prod' => '生产环境',
], 'dev');
```

### 输出格式化

```php
// 基础输出
$out->line('普通文本');
$out->info('信息文本');
$out->warn('警告文本');
$out->error('错误文本');
$out->success('成功文本');

// 带颜色输出
$out->line('红色文本', 'red');
$out->line('加粗文本', 'bold');

// 表格输出
$out->table(['ID', '名称', '状态'], [
    ['ID' => 1, '名称' => '张三', '状态' => '活跃'],
    ['ID' => 2, '名称' => '李四', '状态' => '离线'],
]);

// 进度条
for ($i = 0; $i <= 100; $i++) {
    $out->progress($i, 100);
    usleep(50000);
}

// JSON 输出
$out->json(['status' => 'ok', 'data' => $result]);
```

### 命令分组

```php
use Kode\Console\CommandGroup;

// 创建命令组
$databaseGroup = new CommandGroup('database', '数据库操作');
$databaseGroup->addCommand(new MigrateCommand());
$databaseGroup->addCommand(new SeedCommand());

$kernel->addGroup($databaseGroup);
```

### 中间件

```php
use Kode\Console\Contract\IsMiddleware;

class TimingMiddleware implements IsMiddleware
{
    public function handle(Input $in, Output $out, callable $next): int
    {
        $start = microtime(true);
        
        $result = $next($in, $out);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        $out->line("\n执行时间: {$duration}ms", 'gray');
        
        return $result;
    }
}

$kernel->addMiddleware(new TimingMiddleware());
```

### 事件系统

```php
use Kode\Console\EventManager;
use Kode\Console\Event;

$eventManager = new EventManager();

// 监听事件
$eventManager->listen('command.executing', function (Event $event) {
    echo "即将执行: {$event->data['command']->name}\n";
});

$kernel->setEventManager($eventManager);
```

### 命令别名和示例

```php
class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('serve', '启动开发服务器', 'serve {port?} {--host=localhost}');
        $this->sig($this->usage);
        
        // 添加别名
        $this->alias(['server', 'start']);
        
        // 添加示例
        $this->example('serve', '在当前目录启动服务器');
        $this->example('serve 8080 --host=0.0.0.0', '指定端口和主机启动');
        
        // 设置相关命令
        $this->related(['migrate', 'seed']);
        
        // 设置分组
        $this->group('development');
    }
}
```

## 🏗️ 架构设计

```
kode/console
├── Command.php             # 命令基类
├── Kernel.php              # 控制台内核
├── Input.php               # 输入解析器
├── Output.php              # 输出封装
├── Signature.php           # 签名解析器
├── Event.php               # 事件类
├── EventManager.php        # 事件管理器
├── CommandGroup.php        # 命令分组
├── Contract/               # 接口定义
│   ├── IsCommand.php
│   ├── IsInput.php
│   ├── IsOutput.php
│   ├── IsKernel.php
│   ├── IsEvent.php
│   ├── IsEventManager.php
│   └── IsMiddleware.php
├── Helper/                 # 工具类
│   └── Reflector.php
├── Middleware/             # 中间件
│   └── LoggingMiddleware.php
└── Listener/               # 监听器
    └── CommandLogger.php
```

## 📋 API 参考

### Command 类

| 方法 | 说明 |
|------|------|
| `fire(Input, Output): int` | 执行命令（抽象方法） |
| `sig(string): static` | 注册命令签名 |
| `alias(array): static` | 设置命令别名 |
| `example(string, string): static` | 添加使用示例 |
| `related(array): static` | 设置相关命令 |
| `group(string): static` | 设置命令分组 |
| `showHelp(Input, Output): void` | 显示帮助信息 |

### Input 类

| 方法 | 说明 |
|------|------|
| `arg(string\|int, mixed): mixed` | 获取参数值 |
| `opt(string): mixed` | 获取选项值 |
| `flag(string): bool` | 检查标志是否存在 |
| `has(string): bool` | 检查参数是否存在 |
| `cast(mixed, string): mixed` | 类型转换 |
| `validate(string, mixed, array): bool` | 参数验证 |
| `ask(string, string): string` | 询问用户输入 |
| `confirm(string, bool): bool` | 确认操作 |
| `choice(string, array, mixed): mixed` | 选择选项 |

### Output 类

| 方法 | 说明 |
|------|------|
| `line(string, string): void` | 输出一行文本 |
| `info(string): void` | 输出信息（蓝色） |
| `warn(string): void` | 输出警告（黄色） |
| `error(string): void` | 输出错误（红色） |
| `success(string): void` | 输出成功（绿色） |
| `raw(string): void` | 输出原始文本 |
| `styled(string, string): void` | 带样式输出 |
| `table(array, array): void` | 表格输出 |
| `progress(int, int, int): void` | 进度条输出 |
| `json(mixed): void` | JSON 输出 |

### Kernel 类

| 方法 | 说明 |
|------|------|
| `add(string): static` | 注册命令 |
| `alias(string, string): static` | 添加命令别名 |
| `addGroup(CommandGroup): static` | 添加命令组 |
| `addMiddleware(IsMiddleware): static` | 添加中间件 |
| `setEventManager(IsEventManager): static` | 设置事件管理器 |
| `boot(array): int` | 运行控制台 |
| `all(): array` | 获取所有命令 |

## 📝 开发

### 环境要求

- PHP >= 8.1
- Composer 2.x

### 安装依赖

```bash
composer install
```

### 静态分析

```bash
composer analyse
```

## 📄 许可证

Apache 2.0 License

---

> 🌟 **目标**：成为 PHP 社区最轻量、最健壮、最通用的控制台底层包
