<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsOutput;

/**
 * 输出封装类
 * 
 * 提供格式化的控制台输出功能，支持 ANSI 颜色和样式。
 * 自动检测终端环境，在不支持 ANSI 的环境中降级为纯文本输出。
 * 
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
class Output implements IsOutput
{
    /**
     * 是否在 TTY 终端环境中
     */
    protected bool $isTty;
    
    /**
     * 是否支持 ANSI 颜色
     */
    protected bool $supportsAnsi;

    /**
     * 构造函数
     * 
     * 自动检测终端环境并设置 ANSI 支持状态。
     */
    public function __construct()
    {
        $this->isTty = $this->checkTty();
        $this->supportsAnsi = $this->isTty && $this->checkAnsiSupport();
    }

    /**
     * 检查是否在 TTY 终端环境中
     * 
     * @return bool 在 TTY 环境中返回 true，否则返回 false
     */
    protected function checkTty(): bool
    {
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDOUT);
        }
        return true;
    }

    /**
     * 检查是否支持 ANSI 颜色
     * 
     * @return bool 支持 ANSI 返回 true，否则返回 false
     */
    protected function checkAnsiSupport(): bool
    {
        // Windows 10 版本 1511 及以上支持 ANSI
        if (PHP_OS_FAMILY === 'Windows') {
            if (function_exists('sapi_windows_vt100_support')) {
                return sapi_windows_vt100_support(STDOUT);
            }
            return false;
        }
        
        // Unix-like 系统通常支持 ANSI
        return true;
    }

    /**
     * 输出一行文本
     * 
     * @param string $text 文本内容
     * @param string $color 颜色名称（可选）
     */
    public function line(string $text, string $color = ''): void
    {
        if ($color !== '' && $this->supportsAnsi) {
            echo $this->colorize($text, $color) . PHP_EOL;
        } else {
            echo $text . PHP_EOL;
        }
    }

    /**
     * 输出信息文本（蓝色）
     * 
     * @param string $msg 信息内容
     */
    public function info(string $msg): void
    {
        $this->line($msg, 'blue');
    }

    /**
     * 输出警告文本（黄色）
     * 
     * @param string $msg 警告内容
     */
    public function warn(string $msg): void
    {
        $this->line($msg, 'yellow');
    }

    /**
     * 输出错误文本（红色）
     * 
     * @param string $msg 错误内容
     */
    public function error(string $msg): void
    {
        $this->line($msg, 'red');
    }

    /**
     * 输出成功文本（绿色）
     * 
     * @param string $msg 成功内容
     */
    public function success(string $msg): void
    {
        $this->line($msg, 'green');
    }

    /**
     * 输出原始文本（不添加换行）
     * 
     * @param string $text 文本内容
     */
    public function raw(string $text): void
    {
        echo $text;
    }

    /**
     * 为文本添加颜色
     * 
     * @param string $text 文本内容
     * @param string $color 颜色名称
     * @return string 带颜色代码的文本
     */
    protected function colorize(string $text, string $color): string
    {
        $colors = [
            'black' => '0;30',
            'red' => '0;31',
            'green' => '0;32',
            'yellow' => '0;33',
            'blue' => '0;34',
            'purple' => '0;35',
            'cyan' => '0;36',
            'white' => '0;37',
            'bold' => '1;37',
            'bold_red' => '1;31',
            'bold_green' => '1;32',
            'bold_yellow' => '1;33',
            'bold_blue' => '1;34',
            'bold_purple' => '1;35',
            'bold_cyan' => '1;36',
            'bold_white' => '1;37',
        ];

        if (!isset($colors[$color])) {
            return $text;
        }

        return "\033[{$colors[$color]}m{$text}\033[0m";
    }

    /**
     * 带样式的输出
     * 
     * @param string $text 文本内容
     * @param string $style 样式名称（info/success/warn/error）
     */
    public function styled(string $text, string $style = 'info'): void
    {
        match ($style) {
            'error' => $this->error($text),
            'warn' => $this->warn($text),
            'success' => $this->success($text),
            default => $this->info($text),
        };
    }

    /**
     * 表格输出
     * 
     * 以表格形式输出数据。
     * 
     * @param array<int, string> $headers 表头
     * @param array<int, array<string, string>> $rows 数据行
     */
    public function table(array $headers, array $rows): void
    {
        // 计算每列的最大宽度
        $columnWidths = array_map('strlen', $headers);
        
        foreach ($rows as $row) {
            foreach (array_keys($headers) as $i) {
                $value = $row[$headers[$i]] ?? '';
                $columnWidths[$i] = max($columnWidths[$i], strlen($value));
            }
        }
        
        // 输出表头
        $headerLine = '|';
        $separatorLine = '|';
        foreach (array_keys($headers) as $i) {
            $header = str_pad($headers[$i], $columnWidths[$i], ' ', STR_PAD_BOTH);
            $headerLine .= " {$header} |";
            $separatorLine .= str_repeat('-', $columnWidths[$i] + 2) . '|';
        }
        
        $this->line($headerLine);
        $this->line($separatorLine);
        
        // 输出数据行
        foreach ($rows as $row) {
            $line = '|';
            foreach (array_keys($headers) as $i) {
                $value = $row[$headers[$i]] ?? '';
                $value = str_pad($value, $columnWidths[$i], ' ', STR_PAD_RIGHT);
                $line .= " {$value} |";
            }
            $this->line($line);
        }
    }

    /**
     * 进度条输出
     * 
     * 显示执行进度。
     * 
     * @param int $current 当前进度
     * @param int $total 总进度
     * @param int $width 进度条宽度（默认 50）
     */
    public function progress(int $current, int $total, int $width = 50): void
    {
        if ($total <= 0) {
            return;
        }
        
        $percent = min(100, max(0, intval(($current / $total) * 100)));
        $barLength = intval(($percent / 100) * $width);
        $bar = str_repeat('=', $barLength);
        $empty = str_repeat(' ', $width - $barLength);
        
        $this->raw("\r[{$bar}{$empty}] {$percent}% ({$current}/{$total})");
        
        if ($current >= $total) {
            $this->raw(PHP_EOL);
        }
        
        flush();
    }

    /**
     * JSON 格式输出
     * 
     * 以 JSON 格式输出数据。
     * 
     * @param mixed $data 要输出的数据
     */
    public function json(mixed $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
