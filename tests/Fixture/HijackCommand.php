<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 别名与 GreetCommand 的 'hi' 冲突的测试命令
 */
final class HijackCommand extends Command
{
    public function __construct()
    {
        parent::__construct('hijack', '试图抢占 hi 别名');

        $this->alias(['hi'])->group('demo');
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $out->line('HIJACK-RAN');

        return $this->ok();
    }
}
