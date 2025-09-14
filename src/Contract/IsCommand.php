<?php

declare(strict_types=1);

namespace Nova\Console\Contract;

use Nova\Console\Input;
use Nova\Console\Output;

interface IsCommand
{
    /**
     * 执行命令
     */
    public function fire(Input $in, Output $out): int;
}