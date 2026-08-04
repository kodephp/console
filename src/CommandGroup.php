<?php

declare(strict_types=1);

namespace Kode\Console;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * 命令分组
 *
 * 用于在帮助信息中把同类命令聚合展示，例如 `database`、`cache`。
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 *
 * @implements IteratorAggregate<string, Command>
 */
final class CommandGroup implements Countable, IteratorAggregate
{
    /**
     * 组内命令，键为命令名
     *
     * @var array<string, Command>
     */
    private array $commands = [];

    public function __construct(
        private readonly string $name,
        private readonly string $description = '',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 添加命令，并自动打上分组标记
     */
    public function addCommand(Command $command): self
    {
        $command->group($this->name);
        $this->commands[$command->name] = $command;

        return $this;
    }

    /**
     * 批量添加命令
     *
     * @param array<int, Command> $commands
     */
    public function addCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * 组内全部命令
     *
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * @return Traversable<string, Command>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->commands);
    }
}
