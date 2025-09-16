<?php

namespace Kode\Console;

class CommandGroup
{
    private string $name;
    private string $description;
    
    /**
     * @var Command[]
     */
    private array $commands = [];

    public function __construct(string $name, string $description = '')
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function addCommand(Command $command): self
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}