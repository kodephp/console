<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Event;
use Kode\Console\EventManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
#[CoversClass(EventManager::class)]
final class EventTest extends TestCase
{
    // ------------------------------------------------------------------
    // Event
    // ------------------------------------------------------------------

    public function testEventAccessors(): void
    {
        $event = new Event('command.executed', ['code' => 0, 'command' => 'greet']);

        self::assertSame('command.executed', $event->getName());
        self::assertSame(['code' => 0, 'command' => 'greet'], $event->getData());
        self::assertSame(0, $event->get('code'));
        self::assertSame('greet', $event->get('command'));
        self::assertSame('fallback', $event->get('missing', 'fallback'));
        self::assertTrue($event->has('code'));
        self::assertFalse($event->has('missing'));
    }

    // ------------------------------------------------------------------
    // EventManager
    // ------------------------------------------------------------------

    public function testExactListenerIsCalled(): void
    {
        $manager = new EventManager();
        $captured = null;

        $manager->listen('kernel.booting', static function (Event $e) use (&$captured): void {
            $captured = $e->getName();
        });

        $manager->dispatch(new Event('kernel.booting'));
        $manager->dispatch(new Event('other.event'));

        self::assertSame('kernel.booting', $captured);
    }

    public function testWildcardListenerMatchesPrefix(): void
    {
        $manager = new EventManager();
        $seen = [];

        $manager->listen('command.*', static function (Event $e) use (&$seen): void {
            $seen[] = $e->getName();
        });

        $manager->dispatch(new Event('command.executing'));
        $manager->dispatch(new Event('command.executed'));
        $manager->dispatch(new Event('kernel.terminated'));

        self::assertSame(['command.executing', 'command.executed'], $seen);
    }

    public function testRemoveListener(): void
    {
        $manager = new EventManager();
        $count = 0;
        $cb = static function (Event $e) use (&$count): void {
            $count++;
        };

        $manager->listen('x', $cb);
        $manager->dispatch(new Event('x'));
        self::assertSame(1, $count);

        $manager->removeListener('x', $cb);
        $manager->dispatch(new Event('x'));
        self::assertSame(1, $count);
    }

    public function testForgetClearsAllWhenNoEventGiven(): void
    {
        $manager = new EventManager();
        $count = 0;
        $manager->listen('a', static function (Event $e) use (&$count): void {
            $count++;
        });
        $manager->listen('b', static function (Event $e) use (&$count): void {
            $count++;
        });

        $manager->forget();
        $manager->dispatch(new Event('a'));
        $manager->dispatch(new Event('b'));

        self::assertSame(0, $count);
    }

    public function testListenersReturnsMatched(): void
    {
        $manager = new EventManager();
        $manager->listen('command.*', static fn (Event $e) => null);

        self::assertCount(1, $manager->listeners('command.executed'));
        self::assertCount(0, $manager->listeners('kernel.booting'));
    }
}
