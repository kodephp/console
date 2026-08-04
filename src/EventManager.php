<?php

declare(strict_types=1);

namespace Kode\Console;

use Kode\Console\Contract\IsEvent;
use Kode\Console\Contract\IsEventManager;

/**
 * 事件管理器
 *
 * 支持精确事件名与 `command.*` 形式的通配监听。
 *
 * @package Kode\Console
 * @author KodePHP Team
 * @since 1.0.0
 */
final class EventManager implements IsEventManager
{
    /**
     * 监听器表
     *
     * @var array<string, array<int, callable>>
     */
    private array $listeners = [];

    /**
     * 注册监听器，事件名支持结尾通配符，如 `command.*`
     */
    public function listen(string $event, callable $callback): void
    {
        $this->listeners[$event][] = $callback;
    }

    /**
     * 分发事件
     */
    public function dispatch(IsEvent $event): void
    {
        foreach ($this->resolveListeners($event->getName()) as $listener) {
            $listener($event);
        }
    }

    /**
     * 移除监听器
     */
    public function removeListener(string $event, callable $callback): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        $key = array_search($callback, $this->listeners[$event], true);

        if ($key !== false) {
            unset($this->listeners[$event][$key]);
            $this->listeners[$event] = array_values($this->listeners[$event]);
        }
    }

    /**
     * 移除某个事件的全部监听器；不传事件名则清空全部
     */
    public function forget(?string $event = null): void
    {
        if ($event === null) {
            $this->listeners = [];

            return;
        }

        unset($this->listeners[$event]);
    }

    /**
     * 某事件的全部匹配监听器
     *
     * @return array<int, callable>
     */
    public function listeners(string $event): array
    {
        return $this->resolveListeners($event);
    }

    /**
     * 展开精确匹配与通配匹配
     *
     * @return array<int, callable>
     */
    private function resolveListeners(string $event): array
    {
        $matched = $this->listeners[$event] ?? [];

        foreach ($this->listeners as $pattern => $callbacks) {
            if ($pattern === $event || !str_ends_with($pattern, '*')) {
                continue;
            }

            if (str_starts_with($event, substr($pattern, 0, -1))) {
                $matched = [...$matched, ...$callbacks];
            }
        }

        return $matched;
    }
}
