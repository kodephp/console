<?php

namespace Kode\Console;

class OutputExtended extends Output
{
    /**
     * @var array<string, string>
     */
    private array $styles = [
        'info' => "\033[32m",      // 绿色
        'warn' => "\033[33m",      // 黄色
        'error' => "\033[31m",     // 红色
        'debug' => "\033[36m",     // 青色
        'success' => "\033[32m",   // 绿色
        'bold' => "\033[1m",       // 粗体
        'reset' => "\033[0m",      // 重置
    ];

    /**
     * 输出带样式的文本
     */
    public function styled(string $style, string $text): void
    {
        if (isset($this->styles[$style])) {
            echo $this->styles[$style] . $text . $this->styles['reset'] . "\n";
        } else {
            echo $text . "\n";
        }
    }

    /**
     * 输出表格
     * @param string[] $headers
     * @param array<array<string>> $rows
     */
    public function table(array $headers, array $rows): void
    {
        // 计算每列的最大宽度
        $columnWidths = array_map('strlen', $headers);
        
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $columnWidths[$i] = max($columnWidths[$i] ?? 0, strlen((string)$cell));
            }
        }
        
        // 输出表头
        $headerLine = '';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $columnWidths[$i] + 2);
        }
        echo $headerLine . "\n";
        
        // 输出分隔线
        $separatorLine = '';
        foreach ($columnWidths as $width) {
            $separatorLine .= str_repeat('-', $width + 2);
        }
        echo $separatorLine . "\n";
        
        // 输出数据行
        foreach ($rows as $row) {
            $rowLine = '';
            foreach ($row as $i => $cell) {
                $rowLine .= str_pad((string)$cell, $columnWidths[$i] + 2);
            }
            echo $rowLine . "\n";
        }
    }

    /**
     * 输出进度条
     */
    public function progress(int $current, int $total, int $width = 50): void
    {
        $percent = $total > 0 ? $current / $total : 0;
        $bar = str_repeat('=', (int)($percent * $width));
        $empty = str_repeat(' ', $width - strlen($bar));
        
        printf("\r[%s%s] %d%% (%d/%d)", $bar, $empty, $percent * 100, $current, $total);
        
        if ($current >= $total) {
            echo "\n";
        }
    }

    /**
     * 输出JSON格式数据
     */
    public function json(mixed $data, int $flags = 0): void
    {
        echo json_encode($data, $flags | JSON_PRETTY_PRINT) . "\n";
    }
}