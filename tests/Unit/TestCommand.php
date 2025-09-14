<?php

declare(strict_types=1);

namespace Nova\Console\Tests\Unit;

use Nova\Console\Command;
use Nova\Console\Input;
use Nova\Console\Output;

class TestCommand extends Command
{
    public function __construct()
    {
        parent::__construct('test', 'Test command for unit tests');
    }
    
    public function fire(Input $in, Output $out): int
    {
        return 0;
    }
}