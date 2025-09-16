<?php

namespace Kode\Console;

use Kode\Console\Contract\IsKernel;
use Kode\Console\Contract\IsCommand;
use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Contract\IsEventManager;
use Kode\Console\Contract\IsEvent;
use Kode\Console\Helper\Reflector;
use InvalidArgumentException;
use RuntimeException;
use Exception;

class KernelExtended implements IsKernel
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
    public function add(string $cls): static
    {
        if (!class_exists($cls)) {
            throw new InvalidArgumentException("Class {$cls} not found.");
        }

        $ref = Reflector::of($cls);
        $cmd = $ref->newInstance();

        // 移除重复的 instanceof 检查，因为 Reflector::of 已经确保了类型正确性

        $this->cmds[$cmd->name] = $cmd;
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
     * 添加命令组
     */
    public function addGroup(CommandGroup $group): static
    {
        $this->groups[$group->getName()] = $group;
        return $this;
    }

    /**
     * 添加中间件
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

    /**
     * 获取事件管理器
     */
    public function getEventManager(): ?IsEventManager
    {
        return $this->eventManager;
    }

    /**
     * 运行控制台
     */
    public function boot(array $argv): int
    {
        // 触发启动事件
        if ($this->eventManager) {
            $this->eventManager->dispatch(new Event('kernel.booting', ['argv' => $argv]));
        }

        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $cmdName = $argv[1];
        
        // 检查别名
        if (isset($this->aliases[$cmdName])) {
            $cmdName = $this->aliases[$cmdName];
        }

        if (!isset($this->cmds[$cmdName])) {
            echo "Command '{$argv[1]}' not found.\n";
            return 1;
        }

        $cmd = $this->cmds[$cmdName];
        $input = new Input(array_slice($argv, 2));
        $output = new Output();

        // 触发命令执行前事件
        if ($this->eventManager) {
            $this->eventManager->dispatch(new Event('command.executing', [
                'command' => $cmd,
                'input' => $input,
                'output' => $output
            ]));
        }

        try {
            // 执行中间件链
            $result = $this->runWithMiddleware($cmd, $input, $output);
            
            // 触发命令执行后事件
            if ($this->eventManager) {
                $this->eventManager->dispatch(new Event('command.executed', [
                    'command' => $cmd,
                    'input' => $input,
                    'output' => $output,
                    'result' => $result
                ]));
            }

            return $result;
        } catch (Exception $e) {
            // 触发错误事件
            if ($this->eventManager) {
                $this->eventManager->dispatch(new Event('command.error', [
                    'command' => $cmd,
                    'exception' => $e
                ]));
            }
            
            $output->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 通过中间件链执行命令
     */
    private function runWithMiddleware(Command $cmd, Input $input, Output $output): int
    {
        $middlewares = $this->middlewares;
        
        // 创建执行链
        $next = function (Input $input, Output $output) use ($cmd) {
            return $cmd->fire($input, $output);
        };

        // 从后往前构建中间件链
        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $middleware = $middlewares[$i];
            $nextCopy = $next;
            $next = function (Input $input, Output $output) use ($middleware, $nextCopy) {
                return $middleware->handle($input, $output, $nextCopy);
            };
        }

        return $next($input, $output);
    }

    /**
     * 显示帮助信息
     */
    private function showHelp(): void
    {
        echo "Available commands:\n";
        
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

    /**
     * 获取所有命令（逆变输入）
     *
     * @return iterable<Command>
     */
    public function all(): iterable
    {
        return $this->cmds;
    }
}