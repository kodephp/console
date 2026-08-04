<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsCommand;
use Kode\Console\Enum\Color;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Exception\InvalidCommandException;
use Kode\Console\Helper\Reflector;

/**
 * 命令基类
 *
 * 两种定义方式，任选其一：
 *
 * ```php
 * // 1. 构造函数式
 * final class HelloCommand extends Command
 * {
 *     public function __construct()
 *     {
 *         parent::__construct('hello', '输出问候语', 'hello {name?} {--upper:bool}');
 *     }
 *
 *     #[\Override]
 *     public function fire(Input $in, Output $out): int { ... }
 * }
 *
 * // 2. 注解式（PHP 8 Attribute）
 * #[AsCommand(name: 'hello', description: '输出问候语', usage: 'hello {name?} {--upper:bool}')]
 * final class HelloCommand extends Command
 * {
 *     #[\Override]
 *     public function fire(Input $in, Output $out): int { ... }
 * }
 * ```
 *
 * 只要 `usage` 中出现 `{`，签名会自动解析，无需再手工调用 `sig()`。
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
abstract class Command implements IsCommand
{
    /**
     * 命令名称，注册后不可变更
     */
    public readonly string $name;

    /**
     * 命令描述
     */
    public string $desc;

    /**
     * 用法 / 签名定义
     */
    public string $usage;

    /**
     * 命令别名
     *
     * @var array<int, string>
     */
    protected array $aliases = [];

    /**
     * 使用示例
     *
     * @var array<int, array{example: string, description: string}>
     */
    protected array $examples = [];

    /**
     * 相关命令
     *
     * @var array<int, string>
     */
    protected array $related = [];

    /**
     * 所属分组
     */
    protected ?string $group = null;

    /**
     * 解析后的签名
     */
    protected ?Signature $signature = null;

    /**
     * 是否在命令列表中隐藏
     */
    protected bool $hidden = false;

    /**
     * @param string $name  命令名称，留空时读取 #[AsCommand]
     * @param string $desc  命令描述
     * @param string $usage 签名 DSL
     *
     * @throws InvalidCommandException 未定义命令名时抛出
     */
    public function __construct(string $name = '', string $desc = '', string $usage = '')
    {
        $meta = Reflector::commandMeta(static::class);

        $this->name = $name !== '' ? $name : ($meta->name ?? '');
        $this->desc = $desc !== '' ? $desc : ($meta->description ?? '');
        $this->usage = $usage !== '' ? $usage : ($meta->usage ?? '');

        if ($meta !== null) {
            $this->aliases = $meta->aliases;
            $this->group = $meta->group;
            $this->hidden = $meta->hidden;
        }

        if ($this->name === '') {
            throw InvalidCommandException::missingName(static::class);
        }

        if ($this->usage === '') {
            $this->usage = $this->name;
        }

        $this->signature = new Signature($this->usage);
    }

    /**
     * 执行命令
     *
     * @return int 退出码，0 表示成功
     */
    abstract public function fire(Input $in, Output $out): int;

    // ------------------------------------------------------------------
    // 定义
    // ------------------------------------------------------------------

    /**
     * 注册命令签名
     */
    public function sig(string $def): static
    {
        $this->usage = $def;
        $this->signature = new Signature($def);

        return $this;
    }

    /**
     * 设置命令描述
     */
    public function about(string $text): static
    {
        $this->desc = $text;

        return $this;
    }

    /**
     * 设置命令别名
     *
     * @param array<int, string>|string $alias
     */
    public function alias(array|string $alias): static
    {
        $this->aliases = is_string($alias) ? [$alias] : array_values($alias);

        return $this;
    }

    /**
     * 追加使用示例
     */
    public function example(string $example, string $description = ''): static
    {
        $this->examples[] = ['example' => $example, 'description' => $description];

        return $this;
    }

    /**
     * 设置相关命令
     *
     * @param array<int, string> $commands
     */
    public function related(array $commands): static
    {
        $this->related = array_values($commands);

        return $this;
    }

    /**
     * 设置命令分组
     */
    public function group(?string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * 设置是否在命令列表中隐藏
     */
    public function hidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    // ------------------------------------------------------------------
    // 读取
    // ------------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return array<int, array{example: string, description: string}>
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @return array<int, string>
     */
    public function getRelated(): array
    {
        return $this->related;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    // ------------------------------------------------------------------
    // 帮助与校验
    // ------------------------------------------------------------------

    /**
     * 输出命令的详细帮助
     */
    public function showHelp(Input $in, Output $out): void
    {
        $out->line($this->name . ($this->desc === '' ? '' : " - {$this->desc}"), Color::BoldCyan);
        $out->newLine();
        $out->line('用法:', Color::Bold);
        $out->line('  ' . $this->usage);

        if ($this->aliases !== []) {
            $out->newLine();
            $out->line('别名:', Color::Bold);
            $out->line('  ' . implode(', ', $this->aliases));
        }

        $signature = $this->signature;

        if ($signature instanceof Signature) {
            $arguments = $signature->getArguments();

            if ($arguments !== []) {
                $out->newLine();
                $out->line('参数:', Color::Bold);

                foreach ($arguments as $argument) {
                    $out->line(sprintf(
                        '  %-24s %s',
                        $argument->label(),
                        $this->describeDefinition($argument->type->value, $argument->type->isDefault(), $argument->default, $argument->description),
                    ));
                }
            }

            $options = $signature->getOptions();

            if ($options !== []) {
                $out->newLine();
                $out->line('选项:', Color::Bold);

                foreach ($options as $option) {
                    $out->line(sprintf(
                        '  %-24s %s',
                        $option->label(),
                        $this->describeDefinition($option->type->value, $option->type->isDefault(), $option->default, $option->description),
                    ));
                }
            }
        }

        if ($this->examples !== []) {
            $out->newLine();
            $out->line('示例:', Color::Bold);

            foreach ($this->examples as $example) {
                $out->line('  ' . $example['example'], Color::Green);

                if ($example['description'] !== '') {
                    $out->line('    ' . $example['description'], Color::Gray);
                }
            }
        }

        if ($this->related !== []) {
            $out->newLine();
            $out->line('相关命令:', Color::Bold);
            $out->line('  ' . implode(', ', $this->related));
        }
    }

    /**
     * 拼装「类型 + 默认值 + 说明」提示串
     */
    private function describeDefinition(string $type, bool $isDefaultType, mixed $default, string $description): string
    {
        $parts = [];

        if ($description !== '') {
            $parts[] = $description;
        }

        if (!$isDefaultType) {
            $parts[] = "[{$type}]";
        }

        if ($default !== null && $default !== [] && $default !== false) {
            $parts[] = '[默认: ' . $this->stringifyDefault($default) . ']';
        }

        return implode(' ', $parts);
    }

    /**
     * 默认值可读化
     */
    private function stringifyDefault(mixed $default): string
    {
        if (is_bool($default)) {
            return $default ? 'true' : 'false';
        }

        if (is_array($default)) {
            return implode(',', array_map(static fn (mixed $item): string => is_scalar($item) ? (string) $item : '?', $default));
        }

        return is_scalar($default) ? (string) $default : '?';
    }

    /**
     * 根据签名校验输入
     *
     * 内核会在执行 `fire()` 之前自动调用。
     */
    public function validate(Input $in, Output $out): bool
    {
        $signature = $this->signature;

        if (!$signature instanceof Signature) {
            return true;
        }

        foreach ($signature->getArguments() as $argument) {
            if ($argument->required && $in->arg($argument->name) === null) {
                $out->error("缺少必填参数 '{$argument->name}'。");

                return false;
            }
        }

        foreach ($signature->getOptions() as $option) {
            // `{--opt=}` 表示「一旦使用就必须带值」，而非「必须提供该选项」
            if ($option->valueRequired && $in->provided($option->name) && $in->opt($option->name) === null) {
                $out->error("选项 '--{$option->name}' 需要一个值。");

                return false;
            }
        }

        return true;
    }

    // ------------------------------------------------------------------
    // 便捷返回
    // ------------------------------------------------------------------

    /**
     * 成功返回
     */
    public function ok(): int
    {
        return ExitCode::Success->value;
    }

    /**
     * 输出错误并返回退出码
     */
    public function fail(Output $out, string $message, int|ExitCode $code = ExitCode::Failure): int
    {
        $out->error($message);

        return ExitCode::normalize($code);
    }
}
