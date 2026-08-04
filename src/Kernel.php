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
    public const string VERSION = '4.0.0';

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

        $this->cmds[$command->name] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->aliases[$alias] ??= $command->name;
        }

        return $this;
    }

    /**
     * 添加命令别名
     */
    public function alias(string $alias, string $commandName): static
    {
        $this->aliases[$alias] = $commandName;

        return $this;
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
        $out = $this->getOutput();
        $this->applyGlobalFlags($argv, $out);

        $this->dispatch(self::EVENT_BOOTING, ['argv' => $argv]);

        $requested = $argv[1] ?? null;

        if ($requested === null || $requested === 'list') {
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
            } else {
                $this->showHelp($out);
            }

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

        $this->dispatch(self::EVENT_COMMAND_EXECUTING, [
            'command' => $command->name,
            'argv' => $in->raw(),
        ]);

        try {
            $code = $this->runWithMiddleware($command, $in, $out);

            $this->dispatch(self::EVENT_COMMAND_EXECUTED, [
                'command' => $command->name,
                'code' => $code,
            ]);

            return $this->terminate($code);
        } catch (Throwable $e) {
            $this->dispatch(self::EVENT_COMMAND_ERROR, [
                'command' => $command->name,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

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
        foreach ($argv as $token) {
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
        $this->dispatch(self::EVENT_TERMINATED, ['code' => $code]);

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
