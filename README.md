# Kode Console

> **健壮、通用的 PHP 控制台组件**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-Apache%202.0-green.svg)](LICENSE)

## 📦 简介

`kode/console` 是一个专为现代 PHP 应用设计的**通用控制台工具包**，基于 PHP 8.3+ 现代特性构建，提供轻量、解耦、可扩展的命令行开发体验。

### ✨ 特性

- ✅ **PHP 8.3+ 原生支持** — 使用 `enum`、`#[Attribute]`、`#[Override]`、`json_validate()`、类型化类常量、`hrtime()` 等现代特性
- ✅ **两种命令定义方式** — 构造函数式与声明式 `#[AsCommand]` 注解，任选其一
- ✅ **类型安全** — PHPStan 级别 9 静态分析零错误，全量单元测试覆盖
- ✅ **智能参数解析** — 位置参数命名绑定、短选项别名、类型自动转换、默认值填充、`--` 终止符、负数识别
- ✅ **丰富的输入校验** — `required` / `numeric` / `int` / `min` / `max` / `in` / `regex` 规则
- ✅ **框架无关** — 可被 Laravel、Symfony、ThinkPHP 等任意框架集成
- ✅ **完整 I/O** — STDOUT/STDERR 分流、ANSI 装饰探测、详细度分级、CJK 宽字符表格对齐
- ✅ **可观测性** — 事件系统（支持 `command.*` 通配监听）、中间件链、耗时统计、JSON Lines 日志

## 📦 安装

```bash
composer require kode/console
```

## 🚀 快速开始

### 方式一：构造函数式

```php
<?php

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

final class HelloCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'hello',                        // 命令名
            '输出问候语',                    // 描述
            'hello {name?} {--upper:bool}'  // 签名 DSL
        );
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $name = $in->arg('name', 'World');
        $greeting = "Hello, {$name}!";

        if ($in->flag('upper')) {
            $greeting = strtoupper($greeting);
        }

        $out->success($greeting);

        return $this->ok(); // 便捷成功返回（等价于 return 0）
    }
}
```

### 方式二：声明式 `#[AsCommand]`（推荐）

只要签名 DSL 出现在 `usage` 中，即可省去构造函数：

```php
<?php

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

#[AsCommand(
    name: 'hello',
    description: '输出问候语',
    usage: 'hello {name?} {--upper:bool}',
    aliases: ['hi', 'greet'],
    group: 'general',
)]
final class HelloCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $out->success("Hello, {$in->arg('name', 'World')}!");

        return $this->ok();
    }
}
```

> 若同时提供构造函数与注解，**注解优先**；构造函数中传入的 `$name` / `$desc` / `$usage` 仅在注解缺失时生效。

### 运行命令

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Kode\Console\Kernel;

$kernel = new Kernel('My App', '1.0.0');
$kernel->add(HelloCommand::class);

exit($kernel->run($argv));
```

```bash
php console.php hello John
# 输出: Hello, John!

php console.php hello --upper
# 输出: HELLO, WORLD!

php console.php list        # 列出全部命令
php console.php hello --help # 命令帮助
php console.php --version    # 版本号
```

## 📖 详细文档

### 命令签名 DSL

一行 DSL 同时描述命令名、位置参数与选项：

```
app:serve {root?} {files*} {--host=127.0.0.1} {--port|-p:int=8080} {--secure:bool} {--tag : 备注}
```

#### 位置参数

| 格式 | 说明 |
|------|------|
| `{name}` | 必填参数 |
| `{name?}` | 可选参数 |
| `{name=default}` | 带默认值（隐含可选） |
| `{name:int}` | 带类型 |
| `{name:int=100}` | 带类型 + 默认值 |
| `{files*}` | 可变参数，收集其后全部位置参数 |
| `{name : 描述}` | 追加帮助说明（空格 + 冒号 + 空格分隔） |

#### 选项

| 格式 | 说明 |
|------|------|
| `{--flag}` | 布尔标志 |
| `{--opt=}` | 一旦使用就必须带值 |
| `{--opt=default}` | 带默认值的选项 |
| `{--opt:int}` | 带类型的选项 |
| `{--opt\|-o:int=8080}` | 短别名 + 类型 + 默认值（长短名顺序任意） |

#### 支持的类型

- `string` — 字符串（默认）
- `int` / `integer` — 整数
- `float` / `double` / `number` — 浮点数
- `bool` / `boolean` — 布尔值（识别 `1/true/yes/on` 等）
- `array` / `list` / `csv` — 数组（逗号分隔）
- `json` — JSON（依赖 PHP 8.3 的 `json_validate` 校验，非法 JSON 返回 `null`）

### 输入处理

```php
// 获取位置参数（可按下标或按名称）
$name = $in->arg(0, 'default');
$name = $in->arg('name', 'default');

// 获取选项值（短别名自动归一化到长名）
$port = $in->opt('port');

// 检查标志 / 是否显式提供
$verbose = $in->flag('v');
$hasPort = $in->provided('port');

// 类型转换
$port = $in->cast($in->opt('port'), 'int');

// 参数验证（规则：required / numeric / int / min / max / in / regex）
$valid = $in->validate('port', $port, ['required', 'numeric', 'min:1', 'max:65535']);
```

### 交互式输入

```php
$name   = Input::ask('请输入名称', '默认名称');
$confirm = Input::confirm('确定要删除吗？', false);
$choice = Input::choice('请选择环境', [
    'dev'  => '开发环境',
    'prod' => '生产环境',
], 'dev');
```

### 输出格式化

```php
$out->line('普通文本');
$out->info('信息文本');   // 蓝
$out->success('成功文本'); // 绿
$out->comment('提示文本'); // 青
$out->warn('警告文本');    // 黄，写 STDERR
$out->error('错误文本');    // 红，写 STDERR

// 带颜色（颜色名或别名：purple→magenta、grey→gray 等）
$out->line('红色文本', 'red');
$out->line('加粗文本', 'bold');

// 表格（CJK 全角字符按 2 列对齐）
$out->table(['ID', '名称', '状态'], [
    ['ID' => 1, '名称' => '张三', '状态' => '活跃'],
    ['ID' => 2, '名称' => '李四', '状态' => '离线'],
]);

// 进度条
for ($i = 0; $i <= 100; $i += 10) {
    $out->progress($i, 100);
    usleep(20000);
}

// JSON 输出
$out->json(['status' => 'ok', 'data' => $result]);
```

### 命令分组

```php
use Kode\Console\CommandGroup;

$databaseGroup = new CommandGroup('database', '数据库操作');
$databaseGroup->addCommand(new MigrateCommand());
$databaseGroup->addCommand(new SeedCommand());

$kernel->addGroup($databaseGroup);
```

### 中间件

```php
use Kode\Console\Contract\IsMiddleware;

final class TimingMiddleware implements IsMiddleware
{
    #[\Override]
    public function handle(Input $in, Output $out, callable $next): int
    {
        $start = hrtime(true);
        $result = $next($in, $out);
        $ms = (hrtime(true) - $start) / 1_000_000;
        $out->debug(sprintf('耗时 %.2f ms', $ms));

        return $result;
    }
}

$kernel->addMiddleware(new TimingMiddleware());
```

### 事件系统

```php
use Kode\Console\Event;
use Kode\Console\EventManager;

$eventManager = new EventManager();

// 通配监听所有 command.* 事件
$eventManager->listen('command.*', function (Event $event): void {
    echo "事件: {$event->getName()}\n";
});

$kernel->setEventManager($eventManager);
```

内置事件：`kernel.booting` / `kernel.terminated` / `command.executing` / `command.executed` / `command.error`。

### 命令别名与示例

```php
#[AsCommand(name: 'serve', group: 'development', aliases: ['server', 'start'])]
final class ServeCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $this->example('serve', '在当前目录启动服务器');
        $this->example('serve 8080 --host=0.0.0.0', '指定端口和主机启动');
        $this->related(['migrate', 'seed']);

        // ...
        return $this->ok();
    }
}
```

## 🏗️ 架构设计

```
kode/console
├── Command.php             # 命令基类
├── Kernel.php              # 控制台内核（注册 / 分发 / 中间件 / 异常兜底）
├── Input.php               # 输入解析器
├── Output.php              # 输出封装（流可注入，便于测试）
├── Signature.php           # 签名 DSL 解析器
├── Event.php               # 不可变事件对象
├── EventManager.php        # 事件管理器（支持通配监听）
├── CommandGroup.php        # 命令分组
├── Attribute/              # #[AsCommand] 声明式注解
├── Definition/             # Argument / Option 不可变值对象
├── Enum/                   # ArgType / Color / ExitCode / Verbosity
├── Exception/              # ConsoleException / CommandNotFoundException / InvalidCommandException
├── Contract/               # 接口定义（IsCommand / IsInput / IsOutput / IsKernel / IsEvent / IsEventManager / IsMiddleware）
├── Helper/                 # Reflector（注解缓存 + 实例化）
├── Middleware/             # LoggingMiddleware（耗时统计）
└── Listener/               # CommandLogger（JSON Lines 日志）
```

## 📋 API 参考

### Command 类

| 方法 | 说明 |
|------|------|
| `fire(Input, Output): int` | 执行命令（抽象方法，需 `#[\Override]`） |
| `sig(string): static` | 注册命令签名 |
| `about(string): static` | 设置描述 |
| `alias(array\|string): static` | 设置命令别名 |
| `example(string, string): static` | 添加使用示例 |
| `related(array): static` | 设置相关命令 |
| `group(?string): static` | 设置命令分组 |
| `hidden(bool): static` | 在命令列表中隐藏 |
| `validate(Input, Output): bool` | 执行前自动校验（必填 / 必带值选项） |
| `showHelp(Input, Output): void` | 显示帮助信息 |
| `ok(): int` | 返回成功退出码 `0` |
| `fail(Output, string, int\|ExitCode): int` | 输出错误并返回退出码（默认 `1`） |

### Input 类

| 方法 | 说明 |
|------|------|
| `arg(string\|int, mixed): mixed` | 获取参数值（下标或名称） |
| `opt(string, mixed): mixed` | 获取选项值 |
| `flag(string): bool` | 检查标志是否存在 |
| `has(string\|int): bool` | 检查参数 / 选项 / 标志是否存在 |
| `provided(string): bool` | 选项是否由用户显式提供（区别于默认值） |
| `cast(mixed, string\|ArgType): mixed` | 类型转换 |
| `validate(string, mixed, array): bool` | 参数验证 |
| `ask / confirm / choice` | 交互式输入（可注入流，便于测试） |

### Output 类

| 方法 | 说明 |
|------|------|
| `line / write / raw` | 基础输出（write 可指定详细度级别） |
| `info / success / comment / warn / error / debug` | 语义化输出 |
| `styled(string, string)` | 按样式名输出 |
| `title / section / listing` | 结构化文本 |
| `table(array, array)` | 表格输出（CJK 对齐） |
| `progress(int, int, int, string)` | 进度条 |
| `json(mixed)` | JSON 输出 |
| `getStream() / getErrorStream()` | 获取底层流（主要用于测试断言） |

### Kernel 类

| 方法 | 说明 |
|------|------|
| `add(string): static` / `addMany(array): static` | 注册命令类 |
| `addCommand(Command): static` | 注册命令实例 |
| `alias(string, string): static` | 添加命令别名 |
| `addGroup(CommandGroup): static` | 添加命令组 |
| `addMiddleware(IsMiddleware): static` | 添加中间件 |
| `setEventManager(IsEventManager): static` | 设置事件管理器 |
| `setOutput(Output): static` | 注入输出（测试场景） |
| `run(?array): int` / `boot(array): int` | 运行控制台 |
| `find(string): ?Command` / `resolve(string): Command` | 查找 / 解析命令（含拼写建议） |
| `has(string): bool` / `all(): array` / `groups(): array` | 查询已注册命令 |

### 枚举

| 枚举 | 说明 |
|------|------|
| `ExitCode` | `Success=0` / `Failure=1` / `InvalidInput=2` / `NotFound=127` |
| `Verbosity` | `Quiet` / `Normal` / `Verbose` / `Debug` |
| `ArgType` | `String` / `Int` / `Float` / `Bool` / `Array` / `Json`（`parse()` 兼容别名） |
| `Color` | 完整 ANSI 颜色 / 样式（`resolve()` 兼容 `purple`→`magenta` 等别名） |

## 📝 开发

### 环境要求

- PHP >= 8.3
- Composer 2.x

### 安装依赖

```bash
composer install
```

### 静态分析与测试

```bash
composer analyse   # PHPStan 级别 9
composer test      # PHPUnit 12
composer check     # 先分析后测试
```

## 📄 许可证

Apache 2.0 License

---

> 🌟 **目标**：成为 PHP 社区最轻量、最健壮、最通用的控制台底层包
