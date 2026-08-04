<?php

declare(strict_types=1);

namespace Kode\Console\Exception;

use Kode\Console\Enum\ExitCode;

/**
 * 命令未找到
 *
 * 携带「你是不是想执行」的候选命令列表，便于内核输出友好提示。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
final class CommandNotFoundException extends ConsoleException
{
    /**
     * @param array<int, string> $suggestions 相近命令建议
     */
    public function __construct(
        private readonly string $commandName,
        private readonly array $suggestions = [],
    ) {
        parent::__construct("命令 '{$commandName}' 未找到。", ExitCode::NotFound->value);
    }

    /**
     * 未找到的命令名
     */
    public function commandName(): string
    {
        return $this->commandName;
    }

    /**
     * 相近命令建议
     *
     * @return array<int, string>
     */
    public function suggestions(): array
    {
        return $this->suggestions;
    }
}
