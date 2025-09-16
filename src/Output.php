<?php

namespace Kode\Console;

use Kode\Console\Contract\IsOutput;

class Output implements IsOutput
{
    protected bool $isTty;
    protected bool $supportsAnsi;

    public function __construct()
    {
        $this->isTty = $this->checkTty();
        $this->supportsAnsi = $this->isTty && $this->checkAnsiSupport();
    }

    protected function checkTty(): bool
    {
        // 检查是否在TTY环境中
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDOUT);
        }
        return true; // 默认假设支持TTY
    }

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

    public function line(string $text, string $color = ''): void
    {
        if ($color !== '' && $this->supportsAnsi) {
            echo $this->colorize($text, $color) . PHP_EOL;
        } else {
            echo $text . PHP_EOL;
        }
    }

    public function info(string $msg): void
    {
        $this->line($msg, 'blue');
    }

    public function warn(string $msg): void
    {
        $this->line($msg, 'yellow');
    }

    public function error(string $msg): void
    {
        $this->line($msg, 'red');
    }

    public function success(string $msg): void
    {
        $this->line($msg, 'green');
    }

    public function raw(string $text): void
    {
        echo $text;
    }

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
     */
    public function styled(string $text, string $style = 'info'): void
    {
        switch ($style) {
            case 'error':
                $this->error($text);
                break;
            case 'warn':
                $this->warn($text);
                break;
            case 'success':
                $this->success($text);
                break;
            case 'info':
            default:
                $this->info($text);
                break;
        }
    }

    /**
     * 表格输出
     * @param array<int, array<string, string>> $rows
     * @param array<string> $headers
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
     * @param int $current 当前进度
     * @param int $total 总进度
     * @param int $width 进度条宽度
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
     * JSON输出
     * @param mixed $data
     */
    public function json(mixed $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}