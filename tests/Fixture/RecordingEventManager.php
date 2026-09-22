<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Fixture;

use Kode\Console\Contract\IsEvent;
use Kode\Console\Contract\IsEventManager;
use RuntimeException;

/**
 * 记录派发了哪些事件的测试事件管理器，可指定某个事件名抛异常
 */
final class RecordingEventManager implements IsEventManager
{
    /** @var array<string, int> */
    public array $counts = [];

    public function __construct(private ?string $throwsOn = null)
    {
    }

    #[\Override]
    public function listen(string $event, callable $callback): void
    {
    }

    #[\Override]
    public function dispatch(IsEvent $event): void
    {
        $name = $event->getName();
        $this->counts[$name] = ($this->counts[$name] ?? 0) + 1;

        if ($name === $this->throwsOn) {
            throw new RuntimeException('listener boom on ' . $name);
        }
    }

    #[\Override]
    public function removeListener(string $event, callable $callback): void
    {
    }
}
