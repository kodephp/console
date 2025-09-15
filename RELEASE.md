# Kode Console v1.0.0 发布说明

我们很高兴地宣布 Kode Console v1.0.0 正式发布！这是一个健壮、通用的 PHP 控制台组件，专为现代 PHP 应用设计。

## 🎉 版本亮点

### 核心功能
- **完整的命令系统**：支持参数、选项和标志的定义与解析
- **灵活的命令签名DSL**：简洁直观的命令签名定义语法
- **安全的反射封装**：防止注入与非法调用的反射工具
- **清晰的输入输出处理**：结构化的输入解析和格式化输出

### 技术特性
- ✅ PHP 8.1+ 原生支持，充分利用最新语言特性
- ✅ 命名简洁无冲突，避免与 PHP 原生函数/类重名
- ✅ 支持协变与逆变，提供类型安全的扩展机制
- ✅ 无全局副作用，不污染 $_SERVER 或 define
- ✅ IDE 完整识别支持，提供代码智能感知
- ✅ 可扩展设计，支持未来协程、多线程等特性

### 架构组件
```
kode/console
├── Command.php             // 命令基类
├── Kernel.php              // 控制台内核
├── Input.php               // 输入解析器
├── Output.php              // 输出封装
├── Signature.php           // 命令签名解析器
├── Contract/               // 接口定义
│   ├── IsCommand.php
│   ├── IsInput.php
│   ├── IsOutput.php
│   └── IsKernel.php
└── Helper/                 // 工具类
    └── Reflector.php
```

## 🧪 使用示例

### 创建命令
```php
use Kode\Console\Command;
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
```

### 运行命令
```php
use Kode\Console\Kernel;

$kernel = new Kernel();
$kernel->add(ServeCommand::class);
exit($kernel->boot($_SERVER['argv']));
```

## 📦 安装

```bash
composer require kode/console
```

## 🎯 适用场景

- 为 kodephp 框架提供底层 CLI 支持
- 任意 PHP 框架的控制台扩展（Laravel、Symfony、Slim等）
- 独立的 CLI 应用开发
- 系统管理工具和自动化脚本

## 🚀 未来规划

- 协程支持
- 多进程/多线程处理
- 事件系统
- 更丰富的输出格式支持

---

**版本**: v1.0.0  
**发布日期**: 2025-09-13  
**许可证**: MIT