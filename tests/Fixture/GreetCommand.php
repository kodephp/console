<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 构造函数式测试命令
 */
final class GreetCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'greet',
            '问候某人',
            'greet {name : 姓名} {times:int=1} {--upper|-u:bool} {--suffix=!}',
        );

        $this->alias(['hi'])->group('demo');
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $times = (int) $in->arg('times');
        $text = 'Hello ' . (string) $in->arg('name') . (string) $in->opt('suffix');

        if ($in->flag('upper')) {
            $text = strtoupper($text);
        }

        for ($i = 0; $i < $times; $i++) {
            $out->line($text);
        }

        return $this->ok();
    }
}
