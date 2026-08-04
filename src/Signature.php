<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Definition\Argument;
use Kode\Console\Definition\Option;
use Kode\Console\Enum\ArgType;

/**
 * 命令签名解析器
 *
 * 用一行 DSL 描述命令的位置参数与选项，解析结果为不可变的
 * {@see Argument} / {@see Option} 值对象，供 {@see Input} 绑定和帮助信息渲染使用。
 *
 * 语法速查：
 * ```
 * app:serve {root?} {files*} {--host=127.0.0.1} {--port|-p:int=8080} {--secure:bool} {--tag : 备注}
 * ```
 *
 * | 写法                  | 含义                         |
 * |-----------------------|------------------------------|
 * | `{name}`              | 必填位置参数                 |
 * | `{name?}`             | 可选位置参数                 |
 * | `{name=默认值}`       | 带默认值（隐含可选）         |
 * | `{name:int}`          | 带类型                       |
 * | `{name:int=100}`      | 带类型 + 默认值              |
 * | `{files*}`            | 可变参数，收集其后全部参数   |
 * | `{--flag}`            | 布尔标志                     |
 * | `{--opt=}`            | 必须显式提供值的选项         |
 * | `{--opt=默认值}`      | 带默认值的选项               |
 * | `{--opt|-o:int=8080}` | 短别名 + 类型 + 默认值       |
 * | `{--opt : 说明文本}`  | 追加说明（空格冒号空格分隔） |
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
final class Signature
{
    /**
     * 描述文本分隔符（空格 + 冒号 + 空格），用于与类型标注的冒号区分
     */
    private const string DESCRIPTION_SEPARATOR = ' : ';

    /**
     * 命令名称
     */
    private string $name = '';

    /**
     * 位置参数定义
     *
     * @var array<string, Argument>
     */
    private array $arguments = [];

    /**
     * 选项定义
     *
     * @var array<string, Option>
     */
    private array $options = [];

    /**
     * 短别名到长选项名的映射
     *
     * @var array<string, string>
     */
    private array $shortcuts = [];

    /**
     * @param string $definition 签名定义字符串
     */
    public function __construct(private readonly string $definition)
    {
        $this->parse($definition);
    }

    /**
     * 解析签名
     */
    private function parse(string $signature): void
    {
        $signature = trim($signature);

        $bracePos = strpos($signature, '{');
        $this->name = trim($bracePos === false ? $signature : substr($signature, 0, $bracePos));

        if ($bracePos === false) {
            return;
        }

        if (preg_match_all('/\{([^}]*)\}/u', $signature, $matches) === false) {
            return;
        }

        foreach ($matches[1] as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            [$token, $description] = $this->splitDescription($token);

            if (str_starts_with($token, '-')) {
                $this->parseOption($token, $description);
            } else {
                $this->parseArgument($token, $description);
            }
        }
    }

    /**
     * 拆出「空格冒号空格」后面的说明文本
     *
     * @return array{0: string, 1: string}
     */
    private function splitDescription(string $token): array
    {
        $pos = strpos($token, self::DESCRIPTION_SEPARATOR);

        if ($pos === false) {
            return [$token, ''];
        }

        return [
            rtrim(substr($token, 0, $pos)),
            trim(substr($token, $pos + strlen(self::DESCRIPTION_SEPARATOR))),
        ];
    }

    /**
     * 解析位置参数
     */
    private function parseArgument(string $token, string $description): void
    {
        $required = true;
        $variadic = false;
        $rawDefault = null;

        // {name=default} / {name:int=100}
        if (str_contains($token, '=')) {
            [$token, $rawDefault] = explode('=', $token, 2);
            $required = false;
        }

        // {name:int}
        $type = ArgType::String;
        if (str_contains($token, ':')) {
            [$token, $rawType] = explode(':', $token, 2);
            $type = ArgType::parse($rawType);
        }

        $name = trim($token);

        // {files*} 可变参数
        if (str_ends_with($name, '*')) {
            $variadic = true;
            $name = substr($name, 0, -1);
            $type = $type === ArgType::String ? ArgType::Array : $type;
        }

        // {name?}
        if (str_ends_with($name, '?')) {
            $required = false;
            $name = substr($name, 0, -1);
        }

        $name = trim($name);

        if ($name === '') {
            return;
        }

        $this->arguments[$name] = new Argument(
            name: $name,
            type: $type,
            required: $required,
            default: $rawDefault === null ? null : $type->cast($rawDefault),
            variadic: $variadic,
            description: $description,
        );
    }

    /**
     * 解析选项
     */
    private function parseOption(string $token, string $description): void
    {
        $acceptsValue = false;
        $valueRequired = false;
        $rawDefault = null;

        // {--opt=} / {--opt=default}
        if (str_contains($token, '=')) {
            [$token, $rawDefault] = explode('=', $token, 2);
            $acceptsValue = true;

            if ($rawDefault === '') {
                $valueRequired = true;
                $rawDefault = null;
            }
        }

        // {--opt:int}
        $type = ArgType::String;
        $typed = false;
        if (str_contains($token, ':')) {
            [$token, $rawType] = explode(':', $token, 2);
            $type = ArgType::parse($rawType);
            $typed = true;
        }

        // 声明了非 bool 类型即视为接受值
        if (!$acceptsValue && $typed && $type !== ArgType::Bool) {
            $acceptsValue = true;
        }

        [$name, $shortcut] = $this->splitNames($token);

        if ($name === '') {
            return;
        }

        if ($shortcut !== null) {
            $this->shortcuts[$shortcut] = $name;
        }

        $this->options[$name] = new Option(
            name: $name,
            shortcut: $shortcut,
            type: $type,
            acceptsValue: $acceptsValue,
            valueRequired: $valueRequired,
            default: $rawDefault === null ? null : $type->cast($rawDefault),
            description: $description,
        );
    }

    /**
     * 拆分 `--port|-p` / `-p|--port` 形式的长短名
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitNames(string $token): array
    {
        $name = '';
        $shortcut = null;

        foreach (explode('|', $token) as $part) {
            $part = ltrim(trim($part), '-');

            if ($part === '') {
                continue;
            }

            if (strlen($part) === 1 && $name !== '') {
                $shortcut = $part;
            } elseif ($name === '') {
                $name = $part;
            } elseif ($shortcut === null) {
                $shortcut = $part;
            }
        }

        // 仅提供了单字符名，如 {-v}
        if ($name === '' && $shortcut !== null) {
            $name = $shortcut;
            $shortcut = null;
        }

        // 长名比短名后写时纠正顺序，如 {-p|--port}
        if ($shortcut !== null && strlen($name) === 1 && strlen($shortcut) > 1) {
            [$name, $shortcut] = [$shortcut, $name];
        }

        return [$name, $shortcut];
    }

    /**
     * 命令名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 原始签名定义
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    /**
     * 全部位置参数
     *
     * @return array<string, Argument>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * 按名称获取位置参数
     */
    public function getArgument(string $name): ?Argument
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * 按顺序返回位置参数名
     *
     * @return array<int, string>
     */
    public function argumentNames(): array
    {
        return array_keys($this->arguments);
    }

    /**
     * 全部选项
     *
     * @return array<string, Option>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 按名称获取选项
     */
    public function getOption(string $name): ?Option
    {
        return $this->options[$name] ?? null;
    }

    /**
     * 按短别名获取选项
     */
    public function optionForShortcut(string $shortcut): ?Option
    {
        $name = $this->shortcuts[$shortcut] ?? null;

        return $name === null ? null : ($this->options[$name] ?? null);
    }

    /**
     * 短别名映射表
     *
     * @return array<string, string>
     */
    public function getShortcuts(): array
    {
        return $this->shortcuts;
    }
}
