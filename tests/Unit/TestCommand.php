<?php

namespace Kode\Console\Tests\Unit;

use Kode\Console\Command;
use Kode\Console\Input;
use Kode\Console\Output;

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