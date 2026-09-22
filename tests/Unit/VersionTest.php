<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Kernel;
use PHPUnit\Framework\TestCase;

/**
 * 版本常量防漂移：Kernel::VERSION 是 `--version` 与默认 appVersion 的对外口径，
 * 必须与 composer.json 的 version 同源，否则发布后用户看到的版本号是旧的。
 */
final class VersionTest extends TestCase
{
    public function testVersionConstantMatchesComposerManifest(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            $manifest['version'],
            Kernel::VERSION,
            'src/Kernel.php 的 VERSION 与 composer.json 的 version 不一致，发版时漏改了一处'
        );
    }
}
