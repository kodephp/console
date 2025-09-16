<?php

namespace Kode\Console\Contract;

interface IsEventManager
{
    public function listen(string $event, callable $callback): void;
    public function dispatch(IsEvent $event): void;
    public function removeListener(string $event, callable $callback): void;
}