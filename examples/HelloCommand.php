<?php

declare(strict_types=1);

namespace Kode\Console\Examples;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例：注解式命令
 *
 * 使用 #[AsCommand] 声明元信息，无需编写构造函数。
 */
#[AsCommand(
    name: 'hello',
    description: '输出问候语',
    usage: 'hello {name=World : 被问候的对象} {--upper|-u:bool : 转为大写}',
    aliases: ['hi', 'greet'],
    group: 'general',
)]
final class HelloCommand extends Command
{
    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        // 命名参数已由签名自动绑定，缺省时回落到 DSL 中的默认值
        $greeting = sprintf('你好, %s!', $in->arg('name'));

        if ($in->flag('upper')) {
            $greeting = mb_strtoupper($greeting);
        }

        $out->success($greeting);

        return $this->ok();
    }
}
