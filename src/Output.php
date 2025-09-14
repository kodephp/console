<?php

declare(strict_types=1);

namespace Nova\Console;

use Nova\Console\Contract\IsOutput;

class Output implements IsOutput
{
    // ANSI 颜色代码
    protected const COLORS = [
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

    /**
     * 输出一行文本
     */
    public function line(string $text, string $color = ''): void
    {
        echo $this->format($text, $color) . PHP_EOL;
    }

    /**
     * 输出信息消息
     */
    public function info(string $msg): void
    {
        $this->line($msg, 'cyan');
    }

    /**
     * 输出警告消息
     */
    public function warn(string $msg): void
    {
        $this->line($msg, 'yellow');
    }

    /**
     * 输出错误消息
     */
    public function error(string $msg): void
    {
        $this->line($msg, 'bold_red');
    }

    /**
     * 输出成功消息
     */
    public function success(string $msg): void
    {
        $this->line($msg, 'bold_green');
    }

    /**
     * 输出原始文本
     */
    public function raw(string $text): void
    {
        echo $text;
    }

    /**
     * 格式化带颜色的文本
     */
    protected function format(string $text, string $color = ''): string
    {
        if ($color && isset(self::COLORS[$color]) && $this->supportsColors()) {
            return "\033[" . self::COLORS[$color] . "m" . $text . "\033[0m";
        }
        
        return $text;
    }

    /**
     * 检查终端是否支持颜色
     */
    protected function supportsColors(): bool
    {
        // 检查是否在 TTY 环境中运行
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDOUT);
        }
        
        // Windows 检查
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }
        
        return false;
    }
}