<?php

namespace Kode\Console;

class InteractiveInput
{
    /**
     * 询问用户输入
     */
    public static function ask(string $question, string $default = ''): string
    {
        echo $question;
        if ($default !== '') {
            echo " [{$default}]";
        }
        echo ": ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = trim($input);
        return $input === '' ? $default : $input;
    }

    /**
     * 确认操作
     */
    public static function confirm(string $question, bool $default = false): bool
    {
        $suffix = $default ? 'Y/n' : 'y/N';
        echo "{$question} [{$suffix}]: ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = strtolower(trim($input));
        if ($input === '') {
            return $default;
        }
        
        return in_array($input, ['y', 'yes']);
    }

    /**
     * 选择选项
     * @param array<string, string> $choices
     * @param string|int|null $default
     */
    public static function choice(string $question, array $choices, string|int|null $default = null): string|int|null
    {
        echo $question . "\n";
        
        foreach ($choices as $key => $value) {
            echo "{$key}. {$value}\n";
        }
        
        if ($default !== null) {
            echo "Default: " . (string)$default . "\n";
        }
        
        echo "Enter your choice: ";
        
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return $default;
        }
        
        $input = fgets($handle);
        fclose($handle);
        
        if ($input === false) {
            return $default;
        }
        
        $input = trim($input);
        if ($input === '') {
            return $default;
        }
        
        return $choices[$input] ?? $default;
    }
}