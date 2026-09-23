<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsEventManager;
use Kode\Console\Contract\IsKernel;
use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Enum\Color;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Enum\Verbosity;
use Kode\Console\Exception\CommandNotFoundException;
use Kode\Console\Exception\InvalidCommandException;
use Kode\Console\Helper\Reflector;
use Throwable;

/**
 * 控制台内核
 *
 * 负责命令注册、参数分发、中间件编排与异常兜底，是整个组件的入口。
 *
 * ```php
 * exit((new Kernel('My App', '1.0.0'))
 *     ->add(HelloCommand::class)
 *     ->addMiddleware(new LoggingMiddleware())
 *     ->run());
 * ```
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
class Kernel implements IsKernel
{
    /** 组件版本号 */
    public const string VERSION = '4.0.2';

    /**
     * 获取本包版本号（与 composer.json 的 version 交叉核对，漏改由 VersionGuardTest 拦下）。
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /** 内核启动事件 */
    public const string EVENT_BOOTING = 'kernel.booting';

    /** 内核结束事件 */
    public const string EVENT_TERMINATED = 'kernel.terminated';

    /** 命令执行前事件 */
    public const string EVENT_COMMAND_EXECUTING = 'command.executing';

    /** 命令执行后事件 */
    public const string EVENT_COMMAND_EXECUTED = 'command.executed';

    /** 命令异常事件 */
    public const string EVENT_COMMAND_ERROR = 'command.error';

    /** 命令名相似度阈值，用于「你是不是想执行」提示 */
    private const int SUGGESTION_DISTANCE = 3;

    /**
     * 已注册命令
     *
     * @var array<string, Command>
     */
    private array $cmds = [];

    /**
     * 命令分组
     *
     * @var array<string, CommandGroup>
     */
    private array $groups = [];

    /**
     * 中间件
     *
     * @var array<int, IsMiddleware>
     */
    private array $middlewares = [];

    /**
     * 命令别名映射：别名 => 命令名
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * 事件管理器
     */
    private ?IsEventManager $eventManager = null;

    /**
     * 输出对象，未显式设置时惰性创建
     */
    private ?Output $output = null;

    /** 输出对象是否由外部注入（注入的对象跨 run 复用是调用方的意图，内核不重置） */
    private bool $outputInjected = false;

    /** 本次 boot 是否已经派发过 terminated 事件（防止监听器抛错导致二次派发） */
    private bool $terminated = false;

    /**
     * 是否捕获命令异常（关闭后异常将向外抛出，便于测试）
     */
    private bool $catchExceptions = true;

    /**
     * @param string $appName    应用名，用于帮助信息标题
     * @param string $appVersion 应用版本号
     */
    public function __construct(
        private readonly string $appName = 'Kode Console',
        private readonly string $appVersion = self::VERSION,
    ) {
    }

    // ------------------------------------------------------------------
    // 注册
    // ------------------------------------------------------------------

    /**
     * 注册命令类
     *
     * @param class-string<Command> $cls
     *
     * @throws InvalidCommandException
     */
    public function add(string $cls): static
    {
        return $this->addCommand(Reflector::instantiate($cls));
    }

    /**
     * 批量注册命令类
     *
     * @param array<int, class-string<Command>> $classes
     *
     * @throws InvalidCommandException
     */
    public function addMany(array $classes): static
    {
        foreach ($classes as $cls) {
            $this->add($cls);
        }

        return $this;
    }

    /**
     * 注册命令实例
     *
     * @throws InvalidCommandException
     */
    public function addCommand(Command $command): static
    {
        if (isset($this->cmds[$command->name])) {
            throw InvalidCommandException::duplicated($command->name);
        }

        // 命令名撞上已登记的别名：find() 先查别名表，新命令会永远不可达（静默错路由）。
        $takenBy = $this->aliases[$command->name] ?? null;
        if ($takenBy !== null) {
            throw InvalidCommandException::aliasConflict($command->name, $takenBy);
        }

        $this->cmds[$command->name] = $command;

        foreach ($command->getAliases() as $alias) {
            if ($alias === $command->name) {
                continue;
            }

            $this->assertAliasFree($alias, $command->name);
            $this->aliases[$alias] = $command->name;
        }

        return $this;
    }

    /**
     * 添加命令别名
     *
     * @throws InvalidCommandException 别名与已有命令名或其它命令的别名冲突
     */
    public function alias(string $alias, string $commandName): static
    {
        $this->assertAliasFree($alias, $commandName);
        $this->aliases[$alias] = $commandName;

        return $this;
    }

    /**
     * 别名必须既不与某个命令名同名，也不抢走别的命令已有的别名
     *
     * 旧实现是 `$aliases[$alias] ??= …` 静默跳过 / 直接覆写：冲突时命令照样注册成功，
     * 运行时却按别名表跑到另一个命令上——插件各自注册命令，跨插件同名别名即静默错路由。
     *
     * @throws InvalidCommandException
     */
    private function assertAliasFree(string $alias, string $owner): void
    {
        if (isset($this->cmds[$alias])) {
            throw InvalidCommandException::aliasConflict($alias, $this->cmds[$alias]->name);
        }

        $existing = $this->aliases[$alias] ?? null;
        if ($existing !== null && $existing !== $owner) {
            throw InvalidCommandException::aliasConflict($alias, $existing);
        }
    }

    /**
     * 添加命令分组
     */
    public function addGroup(CommandGroup $group): static
    {
        $this->groups[$group->getName()] = $group;

        foreach ($group->getCommands() as $command) {
            $this->cmds[$command->name] ??= $command;

            foreach ($command->getAliases() as $alias) {
                $this->aliases[$alias] ??= $command->name;
            }
        }

        return $this;
    }

    /**
     * 添加中间件（先注册先执行）
     */
    public function addMiddleware(IsMiddleware $middleware): static
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * 设置事件管理器
     */
    public function setEventManager(IsEventManager $eventManager): static
    {
        $this->eventManager = $eventManager;

        return $this;
    }

    public function getEventManager(): ?IsEventManager
    {
        return $this->eventManager;
    }

    /**
     * 注入输出对象（测试或自定义流场景）
     */
    public function setOutput(Output $output): static
    {
        $this->output = $output;
        $this->outputInjected = true;

        return $this;
    }

    public function getOutput(): Output
    {
        return $this->output ??= new Output();
    }

    /**
     * 是否捕获命令异常
     */
    public function catchExceptions(bool $catch = true): static
    {
        $this->catchExceptions = $catch;

        return $this;
    }

    // ------------------------------------------------------------------
    // 运行
    // ------------------------------------------------------------------

    /**
     * 直接使用当前进程的 argv 运行
     *
     * @param array<int, string>|null $argv
     */
    public function run(?array $argv = null): int
    {
        if ($argv === null) {
            /** @var array<int, string> $argv */
            $argv = $_SERVER['argv'] ?? [];
        }

        return $this->boot($argv);
    }

    /**
     * 运行控制台
     *
     * @param array<int, string> $argv 完整命令行参数（含脚本名）
     */
    public function boot(array $argv): int
    {
        $argv = array_values($argv);

        // 内核自持的输出对象每次 boot 重建：常驻进程里同一个 Kernel 会 boot 多次
        // （框架把它注册成容器单例），上一次 `-q`/`--no-ansi` 的 verbosity 与着色状态
        // 否则会粘到下一次运行——表现为"没传 -q 也没有任何输出"。
        // 外部 setOutput() 注入的对象按调用方意图保留，内核不抹它配好的 verbosity/着色。
        if (!$this->outputInjected) {
            $this->output = null;
        }

        $this->terminated = false;

        $out = $this->getOutput();
        $this->applyGlobalFlags($argv, $out);

        $this->dispatch(self::EVENT_BOOTING, ['argv' => $argv]);

        $requested = $argv[1] ?? null;

        if ($requested === null || ($requested === 'list' && !isset($this->cmds['list']))) {
            // 只有没人注册名为 list 的命令时，才把它当作内置帮助词；否则注册方永远跑不到自己的命令。
            $this->showHelp($out);

            return $this->terminate(ExitCode::Success);
        }

        if (in_array($requested, ['--version', '-V'], true)) {
            $out->line("{$this->appName} {$this->appVersion}", Color::BoldGreen);

            return $this->terminate(ExitCode::Success);
        }

        if (in_array($requested, ['help', '--help', '-h'], true)) {
            $target = $argv[2] ?? null;
            $command = $target === null ? null : $this->find($target);

            if ($command instanceof Command) {
                $command->showHelp(new Input([$command->name], $command->getSignature()), $out);

                return $this->terminate(ExitCode::Success);
            }

            if ($target !== null) {
                // 问的是"某个命令的帮助"而该命令不存在：必须非零退出。
                // 旧实现打印全局帮助并返回 0，CI 里 `kode help <拼错的名字>` 永远绿。
                $out->error("命令 '{$target}' 不存在");
                $this->showHelp($out);

                return $this->terminate(ExitCode::NotFound);
            }

            $this->showHelp($out);

            return $this->terminate(ExitCode::Success);
        }

        try {
            $command = $this->resolve($requested);
        } catch (CommandNotFoundException $e) {
            $out->error($e->getMessage());

            if ($e->suggestions() !== []) {
                $out->newLine();
                $out->line('你是不是想执行:', Color::Yellow);

                foreach ($e->suggestions() as $suggestion) {
                    $out->line('  ' . $suggestion, Color::Green);
                }
            } else {
                $this->showHelp($out);
            }

            return $this->terminate(ExitCode::NotFound);
        }

        $in = new Input(array_slice($argv, 1), $command->getSignature());

        if ($in->flag('help') || $in->flag('h')) {
            $command->showHelp($in, $out);

            return $this->terminate(ExitCode::Success);
        }

        if (!$command->validate($in, $out)) {
            $out->newLine();
            $out->line('用法: ' . $command->usage, Color::Gray);

            return $this->terminate(ExitCode::InvalidInput);
        }

        try {
            // 事件派发放进 try：监听器抛错时按 catchExceptions 的既定契约处理，
            // 而不是从 boot() 里逃出去（README 承诺的是 `exit($kernel->run($argv))`，不是裸异常）。
            $this->dispatch(self::EVENT_COMMAND_EXECUTING, [
                'command' => $command->name,
                'argv' => $in->raw(),
            ]);

            $code = $this->runWithMiddleware($command, $in, $out);

            $this->dispatch(self::EVENT_COMMAND_EXECUTED, [
                'command' => $command->name,
                'code' => $code,
            ]);

            return $this->terminate($code);
        } catch (Throwable $e) {
            // 已在处理异常，这里再抛会把原始故障换成"监听器故障"，故只报告不阻断。
            try {
                $this->dispatch(self::EVENT_COMMAND_ERROR, [
                    'command' => $command->name,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            } catch (Throwable $listenerError) {
                $out->error('command.error 监听器异常: ' . $listenerError->getMessage());
            }

            if (!$this->catchExceptions) {
                throw $e;
            }

            $out->error('错误: ' . $e->getMessage());

            if ($out->isVerbose()) {
                $out->error(sprintf('%s in %s:%d', $e::class, $e->getFile(), $e->getLine()));
                $out->error($e->getTraceAsString());
            }

            return $this->terminate(ExitCode::Failure);
        }
    }

    /**
     * 解析全局标志：`-q`、`-v`、`--no-ansi` 等
     *
     * @param array<int, string> $argv
     */
    private function applyGlobalFlags(array $argv, Output $out): void
    {
        // 只按本次 argv 追加设定，不重置：外部 setOutput() 注入的 Output 上调用方自己
        // 配好的 verbosity/着色属调用方意图（测试与中间件调试依赖它），内核无权抹掉。
        // 跨 run 的状态泄漏由 boot() 重建内核自持 Output 来堵。
        foreach ($argv as $token) {
            // `--` 之后的 token 按契约原样交给命令，绝不解释成全局标志。
            // 此前是全量扫描，`kode some:cmd -- -q` 会把整轮输出静默吞掉并对管道返回 0（"空成功"）。
            if ($token === '--') {
                break;
            }

            match (true) {
                $token === '-q', $token === '--quiet' => $out->setVerbosity(Verbosity::Quiet),
                $token === '-v', $token === '--verbose' => $out->setVerbosity(Verbosity::Verbose),
                $token === '-vv', $token === '-vvv', $token === '--debug' => $out->setVerbosity(Verbosity::Debug),
                $token === '--no-ansi', $token === '--no-color' => $out->setDecorated(false),
                $token === '--ansi' => $out->setDecorated(true),
                default => null,
            };
        }
    }

    /**
     * 触发结束事件并归一化退出码
     */
    private function terminate(int|ExitCode $code): int
    {
        $code = ExitCode::normalize($code);

        // 幂等：terminate 可能在 try 与 catch 两条路径上都被碰到，监听器只应看到一次结束。
        if ($this->terminated) {
            return $code;
        }

        $this->terminated = true;

        // 收尾监听器抛错不该把一次已经跑完的命令换成 fatal：命令结果已定，
        // 这里只报告并保留原退出码（常驻进程里一个坏监听器会拖死整条命令链路）。
        try {
            $this->dispatch(self::EVENT_TERMINATED, ['code' => $code]);
        } catch (Throwable $listenerError) {
            $this->getOutput()->error('kernel.terminated 监听器异常: ' . $listenerError->getMessage());
        }

        return $code;
    }

    /**
     * 通过中间件链执行命令
     */
    private function runWithMiddleware(Command $cmd, Input $in, Output $out): int
    {
        $next = static fn (Input $in, Output $out): int => $cmd->fire($in, $out);

        for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
            $middleware = $this->middlewares[$i];
            $previous = $next;
            $next = static fn (Input $in, Output $out): int => $middleware->handle($in, $out, $previous);
        }

        return $next($in, $out);
    }

    // ------------------------------------------------------------------
    // 查找
    // ------------------------------------------------------------------

    /**
     * 查找命令（含别名），未找到返回 null
     */
    public function find(string $name): ?Command
    {
        $name = $this->aliases[$name] ?? $name;

        return $this->cmds[$name] ?? null;
    }

    /**
     * 查找命令，未找到时抛出带建议的异常
     *
     * @throws CommandNotFoundException
     */
    public function resolve(string $name): Command
    {
        $command = $this->find($name);

        if ($command instanceof Command) {
            return $command;
        }

        throw new CommandNotFoundException($name, $this->suggest($name));
    }

    /**
     * 命令是否存在
     */
    public function has(string $name): bool
    {
        return $this->find($name) instanceof Command;
    }

    /**
     * 全部命令
     *
     * @return array<string, Command>
     */
    public function all(): array
    {
        return $this->cmds;
    }

    /**
     * 全部分组
     *
     * @return array<string, CommandGroup>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * 相近命令建议
     *
     * @return array<int, string>
     */
    public function suggest(string $name): array
    {
        $candidates = [...array_keys($this->cmds), ...array_keys($this->aliases)];
        $scored = [];

        foreach ($candidates as $candidate) {
            $distance = levenshtein($name, $candidate);

            if ($distance <= self::SUGGESTION_DISTANCE || str_contains($candidate, $name)) {
                $scored[$candidate] = $distance;
            }
        }

        asort($scored);

        return array_slice(array_keys($scored), 0, 5);
    }

    // ------------------------------------------------------------------
    // 帮助
    // ------------------------------------------------------------------

    /**
     * 输出全局帮助
     */
    protected function showHelp(Output $out): void
    {
        $out->line("{$this->appName} {$this->appVersion}", Color::BoldCyan);
        $out->newLine();
        $out->line('用法:', Color::Bold);
        $out->line('  command [参数] [选项]');
        $out->newLine();
        $out->line('全局选项:', Color::Bold);
        $out->line('  -h, --help        显示帮助信息');
        $out->line('  -V, --version     显示版本号');
        $out->line('  -q, --quiet       静默模式');
        $out->line('  -v, --verbose     输出更多信息');
        $out->line('      --no-ansi     禁用彩色输出');

        $grouped = [];

        foreach ($this->cmds as $command) {
            if ($command->isHidden()) {
                continue;
            }

            $grouped[$command->getGroup() ?? '可用命令'][$command->name] = $command;
        }

        ksort($grouped);

        foreach ($grouped as $groupName => $commands) {
            ksort($commands);
            $description = ($this->groups[$groupName] ?? null)?->getDescription() ?? '';

            $out->newLine();
            $out->line($groupName . ($description === '' ? '' : " ({$description})") . ':', Color::Bold);

            foreach ($commands as $command) {
                $out->line(sprintf('  %-24s %s', $command->name, $command->desc));
            }
        }
    }

    /**
     * 分发事件
     *
     * @param array<string, mixed> $data
     */
    private function dispatch(string $name, array $data = []): void
    {
        $this->eventManager?->dispatch(new Event($name, $data));
    }
}
