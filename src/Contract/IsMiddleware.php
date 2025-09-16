<?php

namespace Kode\Console\Contract;

use Kode\Console\Input;
use Kode\Console\Output;

interface IsMiddleware
{
    public function handle(Input $input, Output $output, callable $next): int;
}