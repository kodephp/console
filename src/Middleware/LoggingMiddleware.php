<?php

namespace Kode\Console\Middleware;

use Kode\Console\Contract\IsMiddleware;
use Kode\Console\Input;
use Kode\Console\Output;

class LoggingMiddleware implements IsMiddleware
{
    public function handle(Input $input, Output $output, callable $next): int
    {
        $startTime = microtime(true);
        $output->line("Starting command execution...", 'info');
        
        $result = $next($input, $output);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $output->line("Command completed in {$duration}ms", 'info');
        
        return $result;
    }
}