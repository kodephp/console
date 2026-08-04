<?php

declare(strict_types=1);

namespace Kode\Console\Enum;

/**
 * 终端颜色与文本样式
 *
 * 统一封装 ANSI SGR 序列，避免在业务代码中硬编码 `\033[0;31m` 这类魔法字符串。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
enum Color: string
{
    case Black = 'black';
    case Red = 'red';
    case Green = 'green';
    case Yellow = 'yellow';
    case Blue = 'blue';
    case Magenta = 'magenta';
    case Cyan = 'cyan';
    case White = 'white';
    case Gray = 'gray';

    case BrightRed = 'bright_red';
    case BrightGreen = 'bright_green';
    case BrightYellow = 'bright_yellow';
    case BrightBlue = 'bright_blue';
    case BrightMagenta = 'bright_magenta';
    case BrightCyan = 'bright_cyan';
    case BrightWhite = 'bright_white';

    case Bold = 'bold';
    case Dim = 'dim';
    case Italic = 'italic';
    case Underline = 'underline';

    case BoldRed = 'bold_red';
    case BoldGreen = 'bold_green';
    case BoldYellow = 'bold_yellow';
    case BoldBlue = 'bold_blue';
    case BoldMagenta = 'bold_magenta';
    case BoldCyan = 'bold_cyan';
    case BoldWhite = 'bold_white';

    /**
     * 旧版本 / 常见写法别名
     *
     * @var array<string, string>
     */
    private const array ALIASES = [
        'purple' => 'magenta',
        'bold_purple' => 'bold_magenta',
        'bright_purple' => 'bright_magenta',
        'grey' => 'gray',
        'default' => 'white',
        'comment' => 'yellow',
        'question' => 'cyan',
        'note' => 'blue',
    ];

    /**
     * SGR 参数
     */
    public function ansi(): string
    {
        return match ($this) {
            self::Black => '0;30',
            self::Red => '0;31',
            self::Green => '0;32',
            self::Yellow => '0;33',
            self::Blue => '0;34',
            self::Magenta => '0;35',
            self::Cyan => '0;36',
            self::White => '0;37',
            self::Gray => '0;90',

            self::BrightRed => '0;91',
            self::BrightGreen => '0;92',
            self::BrightYellow => '0;93',
            self::BrightBlue => '0;94',
            self::BrightMagenta => '0;95',
            self::BrightCyan => '0;96',
            self::BrightWhite => '0;97',

            self::Bold => '1',
            self::Dim => '2',
            self::Italic => '3',
            self::Underline => '4',

            self::BoldRed => '1;31',
            self::BoldGreen => '1;32',
            self::BoldYellow => '1;33',
            self::BoldBlue => '1;34',
            self::BoldMagenta => '1;35',
            self::BoldCyan => '1;36',
            self::BoldWhite => '1;37',
        };
    }

    /**
     * 用当前颜色包裹文本
     */
    public function wrap(string $text): string
    {
        return "\033[" . $this->ansi() . 'm' . $text . "\033[0m";
    }

    /**
     * 宽松解析：接受枚举、颜色名或别名，无法识别时返回 null
     */
    public static function resolve(self|string|null $color): ?self
    {
        if ($color instanceof self) {
            return $color;
        }

        if ($color === null) {
            return null;
        }

        $key = strtolower(trim($color));

        if ($key === '') {
            return null;
        }

        $key = self::ALIASES[$key] ?? $key;

        return self::tryFrom($key);
    }
}
