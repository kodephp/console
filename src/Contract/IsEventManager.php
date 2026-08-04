<?php

declare(strict_types=1);

namespace Kode\Console\Contract;

/**
 * 事件管理器契约
 *
 * @package Kode\Console
 * @since 1.0.0
 */
interface IsEventManager
{
    /**
     * 注册监听器，事件名支持结尾通配符
     */
    public function listen(string $event, callable $callback): void;

    /**
     * 分发事件
     */
    public function dispatch(IsEvent $event): void;

    /**
     * 移除监听器
     */
    public function removeListener(string $event, callable $callback): void;
}
