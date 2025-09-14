<?php

declare(strict_types=1);

namespace Nova\Console;

use Nova\Console\Contract\IsKernel;
use Nova\Console\Helper\Reflector;
use InvalidArgumentException;

class Kernel implements IsKernel
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
    public function add(string $cls): static
    {
        if (!Reflector::isInstantiable($cls)) {
            throw new InvalidArgumentException("Command class {$cls} is not instantiable.");
        }

        $ref = Reflector::of($cls);
        $cmd = $ref->newInstance();
        
        if (!isset($cmd->name)) {
            throw new InvalidArgumentException("Command class {$cls} must have a name property.");
        }

        $this->cmds[$cmd->name] = $cmd;
        return $this;
    }

    /**
     * 运行控制台
     */
    public function boot(array $argv): int
    {
        // 检查是否有命令参数
        if (count($argv) < 2) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[1];
        
        // 显示帮助
        if ($commandName === 'help' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return 0;
        }

        // 查找命令
        if (!isset($this->cmds[$commandName])) {
            echo "Command '{$commandName}' not found." . PHP_EOL;
            $this->showHelp();
            return 1;
        }

        // 执行命令
        $command = $this->cmds[$commandName];
        $input = new Input($argv);
        $output = new Output();
        
        try {
            return $command->fire($input, $output);
        } catch (\Throwable $e) {
            $output->error("Error executing command: " . $e->getMessage());
            return 1;
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

    /**
     * 显示帮助信息
     */
    protected function showHelp(): void
    {
        echo "Available commands:" . PHP_EOL;
        foreach ($this->cmds as $name => $command) {
            $desc = $command->desc ?? 'No description';
            echo "  {$name} - {$desc}" . PHP_EOL;
        }
    }
}