<?php

namespace Kode\Console;

use Kode\Console\Contract\IsEventManager;
use Kode\Console\Contract\IsEvent;

class EventManager implements IsEventManager
{
    /**
     * @var array<string, callable[]>
     */
    private array $listeners = [];

    public function listen(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $callback;
    }

    public function dispatch(IsEvent $event): void
    {
        $eventName = $event->getName();

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }

    public function removeListener(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        $key = array_search($callback, $this->listeners[$event], true);
        if ($key !== false) {
            unset($this->listeners[$event][$key]);
        }
    }
}