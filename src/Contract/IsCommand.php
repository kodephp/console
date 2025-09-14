<?php

namespace Kode\Console\Contract;

use Kode\Console\Input;
use Kode\Console\Output;

interface IsCommand
{
    /**
     * 执行命令
     */
    public function fire(Input $in, Output $out): int;
}