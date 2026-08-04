<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsInput;
use Kode\Console\Definition\Option;
use Kode\Console\Enum\ArgType;

/**
 * 输入解析器
 *
 * 解析命令行参数为「位置参数 / 选项 / 标志」三类，并可与 {@see Signature}
 * 绑定，从而支持命名参数、短选项别名、类型自动转换与默认值填充。
 *
 * 支持的书写形式：
 * - 位置参数：`build src dist`
 * - 长选项：`--port=8080`、`--port 8080`
 * - 短选项：`-p8080`、`-p 8080`、`-abc`（等价于 `-a -b -c`）
 * - 终止符：`--` 之后的内容原样保留，可通过 {@see self::rest()} 获取
 * - 负数不会被误判为选项：`offset -1`
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
class Input implements IsInput
{
    /**
     * 原始参数数组
     *
     * @var array<int, string>
     */
    protected array $raw;

    /**
     * 位置参数（按出现顺序）
     *
     * @var array<int, string>
     */
    protected array $positional = [];

    /**
     * 绑定签名后的命名参数
     *
     * @var array<string, mixed>
     */
    protected array $named = [];

    /**
     * 选项值
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * 布尔标志
     *
     * @var array<string, bool>
     */
    protected array $flags = [];

    /**
     * 显式出现在命令行中的选项 / 标志名
     *
     * @var array<string, true>
     */
    protected array $provided = [];

    /**
     * `--` 之后的原样参数
     *
     * @var array<int, string>
     */
    protected array $rest = [];

    /**
     * 关联的命令签名
     */
    protected ?Signature $signature = null;

    /**
     * @param array<int, string> $argv      命令行参数，首个元素被视为命令名并忽略
     * @param Signature|null     $signature 可选的命令签名
     */
    public function __construct(array $argv, ?Signature $signature = null)
    {
        $this->raw = array_values($argv);
        $this->tokenize();

        if ($signature instanceof Signature) {
            $this->bind($signature);
        }
    }

    /**
     * 绑定命令签名
     *
     * 绑定后将执行：短别名归一化、位置参数命名化、类型转换、默认值填充。
     */
    public function bind(Signature $signature): static
    {
        $this->signature = $signature;
        $this->tokenize();
        $this->applySignature($signature);

        return $this;
    }

    /**
     * 当前绑定的签名
     */
    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    // ------------------------------------------------------------------
    // 解析
    // ------------------------------------------------------------------

    /**
     * 将原始参数切分为位置参数 / 选项 / 标志
     */
    protected function tokenize(): void
    {
        $this->positional = [];
        $this->named = [];
        $this->options = [];
        $this->flags = [];
        $this->provided = [];
        $this->rest = [];

        $tokens = $this->raw;
        array_shift($tokens); // 丢弃命令名
        $tokens = array_values($tokens);

        $count = count($tokens);
        $endOfOptions = false;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if ($endOfOptions) {
                $this->rest[] = $token;
                $this->positional[] = $token;
                continue;
            }

            if ($token === '--') {
                $endOfOptions = true;
                continue;
            }

            if (str_starts_with($token, '--')) {
                $i += $this->parseLongOption(substr($token, 2), $tokens, $i);
                continue;
            }

            if (str_starts_with($token, '-') && $token !== '-' && !$this->isNegativeNumber($token)) {
                $i += $this->parseShortOption(substr($token, 1), $tokens, $i);
                continue;
            }

            $this->positional[] = $token;
        }
    }

    /**
     * 解析长选项，返回额外消耗的 token 数量
     *
     * @param array<int, string> $tokens
     */
    private function parseLongOption(string $body, array $tokens, int $index): int
    {
        if ($body === '') {
            return 0;
        }

        if (str_contains($body, '=')) {
            [$name, $value] = explode('=', $body, 2);
            $this->setOption($name, $value);

            return 0;
        }

        $option = $this->signature?->getOption($body);

        // 签名声明该选项接受值：尝试吃掉下一个 token
        if ($option instanceof Option && $option->acceptsValue) {
            $next = $tokens[$index + 1] ?? null;

            if ($next !== null && !$this->isOptionToken($next)) {
                $this->setOption($body, $next);

                return 1;
            }

            $this->setOption($body, null);

            return 0;
        }

        // 无签名时的启发式：`--opt value` 视为带值选项
        if ($this->signature === null) {
            $next = $tokens[$index + 1] ?? null;

            if ($next !== null && !$this->isOptionToken($next)) {
                $this->setOption($body, $next);

                return 1;
            }
        }

        $this->setFlag($body);

        return 0;
    }

    /**
     * 解析短选项，返回额外消耗的 token 数量
     *
     * @param array<int, string> $tokens
     */
    private function parseShortOption(string $body, array $tokens, int $index): int
    {
        $first = $body[0];
        $option = $this->signature?->optionForShortcut($first);

        if ($option instanceof Option && $option->acceptsValue) {
            // -p8080 / -p=8080
            if (strlen($body) > 1) {
                $this->setOption($option->name, ltrim(substr($body, 1), '='));

                return 0;
            }

            $next = $tokens[$index + 1] ?? null;

            if ($next !== null && !$this->isOptionToken($next)) {
                $this->setOption($option->name, $next);

                return 1;
            }

            $this->setOption($option->name, null);

            return 0;
        }

        // 组合标志 -abc
        foreach (str_split($body) as $char) {
            $this->setFlag($char);
        }

        return 0;
    }

    /**
     * 记录选项值，重复出现时自动聚合为数组
     */
    private function setOption(string $name, ?string $value): void
    {
        $name = $this->resolveName($name);
        $this->provided[$name] = true;

        if ($value === null) {
            $this->options[$name] ??= null;

            return;
        }

        if (array_key_exists($name, $this->options) && $this->options[$name] !== null) {
            $existing = $this->options[$name];
            $this->options[$name] = is_array($existing) ? [...$existing, $value] : [$existing, $value];

            return;
        }

        $this->options[$name] = $value;
    }

    /**
     * 记录布尔标志
     */
    private function setFlag(string $name): void
    {
        $resolved = $this->resolveName($name);

        $this->flags[$name] = true;
        $this->flags[$resolved] = true;
        $this->provided[$resolved] = true;
    }

    /**
     * 短别名归一化为长选项名
     */
    private function resolveName(string $name): string
    {
        $option = $this->signature?->optionForShortcut($name);

        return $option instanceof Option ? $option->name : $name;
    }

    /**
     * 是否是选项形式的 token（用于判断能否作为上一个选项的值）
     */
    private function isOptionToken(string $token): bool
    {
        if (!str_starts_with($token, '-') || $token === '-') {
            return false;
        }

        return !$this->isNegativeNumber($token);
    }

    /**
     * 是否为负数字面量，如 `-1`、`-3.14`
     */
    private function isNegativeNumber(string $token): bool
    {
        return is_numeric($token);
    }

    /**
     * 按签名完成命名化、类型转换与默认值填充
     */
    private function applySignature(Signature $signature): void
    {
        $positional = $this->positional;
        $cursor = 0;

        foreach ($signature->getArguments() as $argument) {
            if ($argument->variadic) {
                $collected = array_slice($positional, $cursor);
                $cursor = count($positional);
                $this->named[$argument->name] = $collected === []
                    ? ($argument->default ?? [])
                    : $collected;
                continue;
            }

            $value = $positional[$cursor] ?? null;
            $cursor++;

            $this->named[$argument->name] = $value === null
                ? $argument->default
                : $argument->type->cast($value);
        }

        foreach ($signature->getOptions() as $option) {
            $name = $option->name;

            if (array_key_exists($name, $this->options) && $this->options[$name] !== null) {
                $this->options[$name] = $option->type->cast($this->options[$name]);
                continue;
            }

            // 声明为 bool 的选项以标志形式出现
            if (($this->flags[$name] ?? false) === true) {
                $this->options[$name] = $option->acceptsValue ? ($option->default ?? true) : true;
                continue;
            }

            if ($option->type === ArgType::Bool) {
                $this->options[$name] = $option->default ?? false;
                continue;
            }

            $this->options[$name] = $option->default;
        }
    }

    // ------------------------------------------------------------------
    // 读取
    // ------------------------------------------------------------------

    /**
     * 获取参数值
     *
     * 传入整数按位置读取，传入字符串按签名命名读取。
     */
    public function arg(string|int $key, mixed $default = null): mixed
    {
        if (is_int($key)) {
            return $this->positional[$key] ?? $default;
        }

        if (array_key_exists($key, $this->named)) {
            return $this->named[$key] ?? $default;
        }

        if (ctype_digit($key)) {
            return $this->positional[(int) $key] ?? $default;
        }

        return $default;
    }

    /**
     * 全部命名参数
     *
     * @return array<string, mixed>
     */
    public function args(): array
    {
        return $this->named;
    }

    /**
     * 全部位置参数
     *
     * @return array<int, string>
     */
    public function positional(): array
    {
        return $this->positional;
    }

    /**
     * 获取选项值
     */
    public function opt(string $name, mixed $default = null): mixed
    {
        $name = $this->resolveName($name);

        if (array_key_exists($name, $this->options) && $this->options[$name] !== null) {
            return $this->options[$name];
        }

        if (($this->flags[$name] ?? false) === true) {
            return true;
        }

        return $default;
    }

    /**
     * 全部选项
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * 检查标志
     */
    public function flag(string $name, bool $default = false): bool
    {
        $resolved = $this->resolveName($name);

        if (($this->flags[$name] ?? false) === true || ($this->flags[$resolved] ?? false) === true) {
            return true;
        }

        $value = $this->options[$resolved] ?? null;

        if ($value === null) {
            return $default;
        }

        return (bool) ArgType::Bool->cast($value);
    }

    /**
     * 全部标志
     *
     * @return array<string, bool>
     */
    public function flags(): array
    {
        return $this->flags;
    }

    /**
     * 参数 / 选项 / 标志是否存在
     */
    public function has(string|int $key): bool
    {
        if (is_int($key)) {
            return isset($this->positional[$key]);
        }

        if (ctype_digit($key)) {
            return isset($this->positional[(int) $key]);
        }

        $resolved = $this->resolveName($key);

        return array_key_exists($key, $this->named)
            || array_key_exists($resolved, $this->options)
            || array_key_exists($resolved, $this->flags);
    }

    /**
     * 选项 / 标志是否由用户在命令行中显式提供（区别于默认值）
     */
    public function provided(string $name): bool
    {
        return isset($this->provided[$this->resolveName($name)]);
    }

    /**
     * `--` 之后的原样参数
     *
     * @return array<int, string>
     */
    public function rest(): array
    {
        return $this->rest;
    }

    /**
     * 原始参数数组
     *
     * @return array<int, string>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    // ------------------------------------------------------------------
    // 转换与校验
    // ------------------------------------------------------------------

    /**
     * 类型转换
     */
    public function cast(mixed $value, ArgType|string $type): mixed
    {
        $type = $type instanceof ArgType ? $type : ArgType::parse($type);

        return $type->cast($value);
    }

    /**
     * 规则校验
     *
     * 支持的规则：`required`、`numeric`、`int`、`boolean`、`min:n`、`max:n`、`in:a,b,c`、`regex:/.../`
     *
     * @param array<int, string> $rules
     */
    public function validate(string $key, mixed $value, array $rules): bool
    {
        foreach ($rules as $rule) {
            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

            $passed = match ($name) {
                'required' => $value !== null && $value !== '' && $value !== [],
                'numeric' => is_scalar($value) && is_numeric($value),
                'int', 'integer' => is_scalar($value) && filter_var((string) $value, FILTER_VALIDATE_INT) !== false,
                'bool', 'boolean' => is_bool($value) || in_array(strtolower((string) ArgType::String->cast($value)), ['1', '0', 'true', 'false', 'yes', 'no', 'on', 'off'], true),
                'min' => (float) ArgType::Float->cast($value) >= (float) $parameter,
                'max' => (float) ArgType::Float->cast($value) <= (float) $parameter,
                'in' => in_array((string) ArgType::String->cast($value), explode(',', (string) $parameter), true),
                'regex' => $parameter !== null && preg_match($parameter, (string) ArgType::String->cast($value)) === 1,
                default => true,
            };

            if (!$passed) {
                return false;
            }
        }

        return true;
    }

    // ------------------------------------------------------------------
    // 交互
    // ------------------------------------------------------------------

    /**
     * 询问用户输入
     *
     * @param resource|null $stream 输入流，默认 STDIN（便于测试注入）
     */
    public static function ask(string $question, string $default = '', mixed $stream = null): string
    {
        echo $question;

        if ($default !== '') {
            echo " [{$default}]";
        }

        echo ': ';

        $line = self::readLine($stream);

        return $line === '' ? $default : $line;
    }

    /**
     * 询问敏感信息（尽可能关闭终端回显）
     *
     * @param resource|null $stream
     */
    public static function secret(string $question, mixed $stream = null): string
    {
        $restore = null;

        if ($stream === null && PHP_OS_FAMILY !== 'Windows' && self::hasStty()) {
            $restore = trim((string) shell_exec('stty -g 2>/dev/null'));
            shell_exec('stty -echo 2>/dev/null');
        }

        echo $question . ': ';
        $line = self::readLine($stream);

        if ($restore !== null && $restore !== '') {
            shell_exec('stty ' . escapeshellarg($restore) . ' 2>/dev/null');
            echo PHP_EOL;
        }

        return $line;
    }

    /**
     * 确认操作
     *
     * @param resource|null $stream
     */
    public static function confirm(string $question, bool $default = false, mixed $stream = null): bool
    {
        echo "{$question} [" . ($default ? 'Y/n' : 'y/N') . ']: ';

        $line = strtolower(self::readLine($stream));

        if ($line === '') {
            return $default;
        }

        return in_array($line, ['y', 'yes', '是'], true);
    }

    /**
     * 单选
     *
     * @param array<string|int, string> $choices 选项列表 [key => label]
     * @param resource|null             $stream
     */
    public static function choice(string $question, array $choices, string|int|null $default = null, mixed $stream = null): string|int|null
    {
        echo $question . PHP_EOL;

        foreach ($choices as $key => $label) {
            echo "  {$key}. {$label}" . PHP_EOL;
        }

        if ($default !== null) {
            echo '默认: ' . $default . PHP_EOL;
        }

        echo '请输入选择: ';

        $line = self::readLine($stream);

        if ($line === '') {
            return $default;
        }

        return array_key_exists($line, $choices) ? $line : $default;
    }

    /**
     * 读取一行输入
     *
     * @param resource|null $stream
     */
    private static function readLine(mixed $stream = null): string
    {
        $handle = $stream;
        $shouldClose = false;

        if (!is_resource($handle)) {
            if (defined('STDIN')) {
                $handle = STDIN;
            } else {
                $handle = fopen('php://stdin', 'rb');
                $shouldClose = true;
            }
        }

        if (!is_resource($handle)) {
            return '';
        }

        $line = fgets($handle);

        if ($shouldClose) {
            fclose($handle);
        }

        return $line === false ? '' : trim($line);
    }

    /**
     * 当前环境是否可用 stty
     */
    private static function hasStty(): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        return shell_exec('command -v stty 2>/dev/null') !== null;
    }
}
