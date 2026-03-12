<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsKernel;
use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Contract\IsEventManager;
use Kode\Console\Helper\Reflector;
use InvalidArgumentException;

/**
 * 控制台内核
 * 
 * 负责命令的注册、调度和执行。
 * 支持命令分组、中间件、事件系统等高级功能。
 * 
 * @package Kode.Console
 * @author KodePHP Team
 * @since 1.0.0
 */
class Kernel implements IsKernel
{
    /**
     * 已注册的命令列表
     * 
     * @var array<string, Command>
     */
    private array $cmds = [];
    
    /**
     * 命令分组列表
     * 
     * @var array<string, CommandGroup>
     */
    private array $groups = [];
    
    /**
     * 中间件列表
     * 
     * @var array<int, IsMiddleware>
     */
    private array $middlewares = [];
    
    /**
     * 事件管理器
     */
    private ?IsEventManager $eventManager = null;
    
    /**
     * 命令别名映射
     * 
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * 注册命令
     * 
     * @param class-string<Command> $cls 命令类名
     * @return static 返回当前实例，支持链式调用
     * @throws InvalidArgumentException 如果命令类不存在
     */
    public function add(string $cls): static
    {
        if (!class_exists($cls)) {
            throw new InvalidArgumentException("命令类 {$cls} 不存在。");
        }

        $ref = Reflector::of($cls);
        $cmd = $ref->newInstance();

        $this->cmds[$cmd->name] = $cmd;
        return $this;
    }

    /**
     * 添加命令别名
     * 
     * @param string $alias 别名
     * @param string $commandName 命令名
     * @return static 返回当前实例，支持链式调用
     */
    public function alias(string $alias, string $commandName): static
    {
        $this->aliases[$alias] = $commandName;
        return $this;
    }

    /**
     * 添加命令组
     * 
     * @param CommandGroup $group 命令组对象
     * @return static 返回当前实例，支持链式调用
     */
    public function addGroup(CommandGroup $group): static
    {
        $this->groups[$group->getName()] = $group;
        return $this;
    }

    /**
     * 添加中间件
     * 
     * @param IsMiddleware $middleware 中间件对象
     * @return static 返回当前实例，支持链式调用
     */
    public function addMiddleware(IsMiddleware $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * 设置事件管理器
     * 
     * @param IsEventManager $eventManager 事件管理器对象
     * @return static 返回当前实例，支持链式调用
     */
    public function setEventManager(IsEventManager $eventManager): static
    {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * 获取事件管理器
     * 
     * @return IsEventManager|null 事件管理器对象，未设置时返回 null
     */
    public function getEventManager(): ?IsEventManager
    {
        return $this->eventManager;
    }

    /**
     * 运行控制台
     * 
     * 解析命令行参数，查找并执行对应的命令。
     * 
     * @param array<int, string> $argv 命令行参数数组
     * @return int 退出码，0 表示成功，非零表示失败
     */
    public function boot(array $argv): int
    {
        // 触发启动事件
        $this->dispatch('kernel.booting', ['argv' => $argv]);

        // 检查是否有命令参数
        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];
        
        // 显示帮助
        if (in_array($commandName, ['help', '--help', '-h'], true)) {
            $this->showHelp();
            return 0;
        }
        
        // 检查别名
        if (isset($this->aliases[$commandName])) {
            $commandName = $this->aliases[$commandName];
        }

        // 查找命令
        $command = $this->findCommand($commandName);

        if (!$command) {
            echo "命令 '{$argv[1]}' 未找到。\n";
            $this->showHelp();
            return 1;
        }
        
        $input = new Input(array_slice($argv, 1));
        $output = new Output();
        
        // 触发命令执行前事件
        $this->dispatch('command.executing', [
            'command' => $command,
            'input' => $input,
            'output' => $output
        ]);

        try {
            // 执行中间件链
            $result = $this->runWithMiddleware($command, $input, $output);
            
            // 触发命令执行后事件
            $this->dispatch('command.executed', [
                'command' => $command,
                'input' => $input,
                'output' => $output,
                'result' => $result
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            // 触发错误事件
            $this->dispatch('command.error', [
                'command' => $command,
                'exception' => $e
            ]);
            
            $output->error("错误: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 查找命令
     * 
     * 先在普通命令中查找，再在命令组中查找。
     * 
     * @param string $name 命令名
     * @return Command|null 命令对象，未找到时返回 null
     */
    private function findCommand(string $name): ?Command
    {
        // 先在普通命令中查找
        if (isset($this->cmds[$name])) {
            return $this->cmds[$name];
        }
        
        // 在命令组中查找
        foreach ($this->groups as $group) {
            foreach ($group->getCommands() as $cmd) {
                if ($cmd->name === $name) {
                    return $cmd;
                }
            }
        }
        
        return null;
    }

    /**
     * 通过中间件链执行命令
     * 
     * @param Command $cmd 命令对象
     * @param Input $input 输入对象
     * @param Output $output 输出对象
     * @return int 退出码
     */
    private function runWithMiddleware(Command $cmd, Input $input, Output $output): int
    {
        $middlewares = $this->middlewares;
        
        // 创建执行链
        $next = fn (Input $input, Output $output): int => $cmd->fire($input, $output);

        // 从后往前构建中间件链
        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $middleware = $middlewares[$i];
            $nextCopy = $next;
            $next = fn (Input $input, Output $output): int => $middleware->handle($input, $output, $nextCopy);
        }

        return $next($input, $output);
    }

    /**
     * 分发事件
     * 
     * @param string $name 事件名
     * @param array $data 事件数据
     */
    private function dispatch(string $name, array $data = []): void
    {
        if ($this->eventManager) {
            $this->eventManager->dispatch(new Event($name, $data));
        }
    }

    /**
     * 获取所有命令
     * 
     * @return array<string, Command> 命令列表
     */
    public function all(): array
    {
        return $this->cmds;
    }

    /**
     * 显示帮助信息
     */
    protected function showHelp(): void
    {
        echo "可用命令:\n";
        
        // 显示普通命令
        foreach ($this->cmds as $cmd) {
            printf("  %-20s %s\n", $cmd->name, $cmd->desc);
        }
        
        // 显示命令组
        foreach ($this->groups as $group) {
            echo "\n{$group->getName()}:\n";
            foreach ($group->getCommands() as $cmd) {
                printf("  %-20s %s\n", $cmd->name, $cmd->desc);
            }
        }
    }
}
