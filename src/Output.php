<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsOutput;
use Kode\Console\Enum\Color;
use Kode\Console\Enum\Verbosity;

/**
 * 输出封装
 *
 * 特性：
 * - 正常输出走 STDOUT，警告 / 错误走 STDERR，便于管道与日志分流
 * - 自动探测终端能力，并遵循 `NO_COLOR` / `FORCE_COLOR` 事实标准
 * - 支持详细度分级（-q / -v / -vvv）
 * - 表格按显示宽度对齐，中日韩全角字符不会错位
 * - 所有写入都通过可注入的流完成，单元测试可直接断言输出内容
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
class Output implements IsOutput
{
    /**
     * 标准输出流
     *
     * @var resource
     */
    protected $stream;

    /**
     * 错误输出流
     *
     * @var resource
     */
    protected $errorStream;

    /**
     * 是否输出 ANSI 转义序列
     */
    protected bool $decorated;

    /**
     * 输出详细度
     */
    protected Verbosity $verbosity;

    /**
     * @param resource|null $stream      标准输出流，默认 STDOUT
     * @param resource|null $errorStream 错误输出流，默认 STDERR
     * @param bool|null     $decorated   是否着色，null 表示自动探测
     */
    public function __construct(
        mixed $stream = null,
        mixed $errorStream = null,
        ?bool $decorated = null,
        Verbosity $verbosity = Verbosity::Normal,
    ) {
        $this->stream = is_resource($stream) ? $stream : self::defaultStream('php://stdout', 'STDOUT');
        $this->errorStream = is_resource($errorStream) ? $errorStream : self::defaultStream('php://stderr', 'STDERR');
        $this->decorated = $decorated ?? $this->detectDecoration($this->stream);
        $this->verbosity = $verbosity;
    }

    // ------------------------------------------------------------------
    // 环境探测
    // ------------------------------------------------------------------

    /**
     * 获取默认流，CLI 环境外回退到 php:// 包装器
     *
     * @return resource
     */
    private static function defaultStream(string $wrapper, string $constant): mixed
    {
        if (defined($constant)) {
            /** @var resource $handle */
            $handle = constant($constant);

            return $handle;
        }

        $handle = fopen($wrapper, 'wb');

        if ($handle === false) {
            $handle = fopen('php://memory', 'w+b');
        }

        /** @var resource $handle */
        return $handle;
    }

    /**
     * 探测是否应当输出颜色
     *
     * @param resource $stream
     */
    protected function detectDecoration(mixed $stream): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (getenv('FORCE_COLOR') !== false) {
            return true;
        }

        if (!$this->isTty($stream)) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            if (function_exists('sapi_windows_vt100_support')) {
                return sapi_windows_vt100_support($stream);
            }

            return getenv('ANSICON') !== false || getenv('WT_SESSION') !== false;
        }

        return getenv('TERM') !== 'dumb';
    }

    /**
     * 是否为交互式终端
     *
     * @param resource $stream
     */
    protected function isTty(mixed $stream): bool
    {
        if (function_exists('stream_isatty')) {
            return stream_isatty($stream);
        }

        if (function_exists('posix_isatty')) {
            return posix_isatty($stream);
        }

        return false;
    }

    // ------------------------------------------------------------------
    // 配置
    // ------------------------------------------------------------------

    public function isDecorated(): bool
    {
        return $this->decorated;
    }

    public function setDecorated(bool $decorated): static
    {
        $this->decorated = $decorated;

        return $this;
    }

    public function getVerbosity(): Verbosity
    {
        return $this->verbosity;
    }

    /**
     * 获取底层标准输出流（主要用于测试断言）
     *
     * @return resource
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * 获取底层错误输出流（主要用于测试断言）
     *
     * @return resource
     */
    public function getErrorStream(): mixed
    {
        return $this->errorStream;
    }

    public function setVerbosity(Verbosity $verbosity): static
    {
        $this->verbosity = $verbosity;

        return $this;
    }

    public function isQuiet(): bool
    {
        return $this->verbosity === Verbosity::Quiet;
    }

    public function isVerbose(): bool
    {
        return $this->verbosity->allows(Verbosity::Verbose);
    }

    // ------------------------------------------------------------------
    // 基础输出
    // ------------------------------------------------------------------

    /**
     * 写入文本（不换行）
     */
    public function write(string $text, Color|string|null $color = null, Verbosity $level = Verbosity::Normal): void
    {
        if (!$this->verbosity->allows($level)) {
            return;
        }

        fwrite($this->stream, $this->format($text, $color));
    }

    /**
     * 输出一行文本
     */
    public function line(string $text = '', Color|string $color = '', Verbosity $level = Verbosity::Normal): void
    {
        $this->write($text . PHP_EOL, $color, $level);
    }

    /**
     * 输出原始文本（不换行、不着色）
     */
    public function raw(string $text): void
    {
        if ($this->isQuiet()) {
            return;
        }

        fwrite($this->stream, $text);
    }

    /**
     * 输出若干空行
     */
    public function newLine(int $count = 1): void
    {
        if ($count > 0) {
            $this->write(str_repeat(PHP_EOL, $count));
        }
    }

    /**
     * 写入错误流
     */
    public function writeError(string $text, Color|string|null $color = null): void
    {
        fwrite($this->errorStream, $this->format($text, $color));
    }

    /**
     * 信息（蓝色）
     */
    public function info(string $msg): void
    {
        $this->line($msg, Color::Blue);
    }

    /**
     * 成功（绿色）
     */
    public function success(string $msg): void
    {
        $this->line($msg, Color::Green);
    }

    /**
     * 提示（青色）
     */
    public function comment(string $msg): void
    {
        $this->line($msg, Color::Cyan);
    }

    /**
     * 调试信息，仅在 -vvv 下输出
     */
    public function debug(string $msg): void
    {
        $this->line($msg, Color::Gray, Verbosity::Debug);
    }

    /**
     * 警告（黄色，写入 STDERR）
     */
    public function warn(string $msg): void
    {
        if ($this->isQuiet()) {
            return;
        }

        $this->writeError($msg . PHP_EOL, Color::Yellow);
    }

    /**
     * 错误（红色，写入 STDERR，即便 quiet 也会输出）
     */
    public function error(string $msg): void
    {
        $this->writeError($msg . PHP_EOL, Color::Red);
    }

    /**
     * 按语义样式输出
     */
    public function styled(string $text, string $style = 'info'): void
    {
        match ($style) {
            'error' => $this->error($text),
            'warn', 'warning' => $this->warn($text),
            'success' => $this->success($text),
            'comment' => $this->comment($text),
            'debug' => $this->debug($text),
            default => $this->info($text),
        };
    }

    /**
     * 标题块
     */
    public function title(string $text): void
    {
        $this->line($text, Color::BoldCyan);
        $this->line(str_repeat('=', max(4, $this->width($text))), Color::Cyan);
    }

    /**
     * 小节标题
     */
    public function section(string $text): void
    {
        $this->newLine();
        $this->line($text, Color::Bold);
        $this->line(str_repeat('-', max(4, $this->width($text))), Color::Gray);
    }

    /**
     * 无序列表
     *
     * @param array<int, string> $items
     */
    public function listing(array $items, string $bullet = '•'): void
    {
        foreach ($items as $item) {
            $this->line("  {$bullet} {$item}");
        }
    }

    /**
     * 为文本套上颜色
     */
    public function format(string $text, Color|string|null $color = null): string
    {
        $resolved = Color::resolve($color);

        if ($resolved === null || !$this->decorated) {
            return $text;
        }

        return $resolved->wrap($text);
    }

    // ------------------------------------------------------------------
    // 复合输出
    // ------------------------------------------------------------------

    /**
     * 表格输出
     *
     * 数据行既可用表头做键，也可使用与表头同序的列表。
     *
     * @param array<int, string>                                     $headers
     * @param array<int, array<array-key, scalar|\Stringable|null>> $rows
     */
    public function table(array $headers, array $rows): void
    {
        $headers = array_values($headers);
        $columns = count($headers);

        if ($columns === 0) {
            return;
        }

        $matrix = [];

        foreach ($rows as $row) {
            $line = [];

            for ($i = 0; $i < $columns; $i++) {
                $value = $row[$headers[$i]] ?? $row[$i] ?? '';
                $line[] = (string) $value;
            }

            $matrix[] = $line;
        }

        $widths = [];

        for ($i = 0; $i < $columns; $i++) {
            $widths[$i] = $this->width($headers[$i]);

            foreach ($matrix as $line) {
                $widths[$i] = max($widths[$i], $this->width($line[$i]));
            }
        }

        $separator = '+';
        $headerLine = '|';

        for ($i = 0; $i < $columns; $i++) {
            $separator .= str_repeat('-', $widths[$i] + 2) . '+';
            $headerLine .= ' ' . $this->pad($headers[$i], $widths[$i]) . ' |';
        }

        $this->line($separator);
        $this->line($headerLine, Color::Bold);
        $this->line($separator);

        foreach ($matrix as $line) {
            $rendered = '|';

            for ($i = 0; $i < $columns; $i++) {
                $rendered .= ' ' . $this->pad($line[$i], $widths[$i]) . ' |';
            }

            $this->line($rendered);
        }

        $this->line($separator);
    }

    /**
     * 进度条
     */
    public function progress(int $current, int $total, int $width = 40, string $label = ''): void
    {
        if ($total <= 0 || $this->isQuiet()) {
            return;
        }

        $current = max(0, min($current, $total));
        $percent = (int) floor($current / $total * 100);
        $filled = (int) floor($percent / 100 * $width);

        $bar = str_repeat('=', $filled);

        if ($filled < $width) {
            $bar .= '>' . str_repeat(' ', max(0, $width - $filled - 1));
        }

        $suffix = $label === '' ? '' : " {$label}";

        $this->raw(sprintf("\r[%s] %3d%% (%d/%d)%s", $bar, $percent, $current, $total, $suffix));

        if ($current >= $total) {
            $this->raw(PHP_EOL);
        }
    }

    /**
     * JSON 输出
     */
    public function json(mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): void
    {
        $encoded = json_encode($data, $flags);

        $this->line($encoded === false ? '{}' : $encoded);
    }

    // ------------------------------------------------------------------
    // 工具
    // ------------------------------------------------------------------

    /**
     * 计算文本显示宽度（全角字符按 2 列计）
     */
    protected function width(string $text): int
    {
        if (function_exists('mb_strwidth')) {
            return mb_strwidth($text, 'UTF-8');
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($chars === false) {
            return strlen($text);
        }

        $width = 0;

        foreach ($chars as $char) {
            // 3 字节及以上的 UTF-8 字符（CJK、全角标点、emoji）按 2 列计算
            $width += strlen($char) >= 3 ? 2 : 1;
        }

        return $width;
    }

    /**
     * 按显示宽度右侧补齐
     */
    protected function pad(string $text, int $width): string
    {
        return $text . str_repeat(' ', max(0, $width - $this->width($text)));
    }
}
