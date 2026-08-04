<?php

declare(strict_types=1);

namespace Kode\Console\Attribute;

use Attribute;

/**
 * 声明式命令定义
 *
 * 使用注解描述命令元信息，子类无需再手写构造函数：
 *
 * ```php
 * #[AsCommand(
 *     name: 'app:serve',
 *     description: '启动开发服务器',
 *     usage: 'app:serve {root?} {--host=127.0.0.1} {--port:int=8080}',
 *     aliases: ['serve'],
 *     group: 'development',
 * )]
 * final class ServeCommand extends Command { ... }
 * ```
 *
 * @package Kode\Console
 * @since 4.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsCommand
{
    /**
     * @param string             $name        命令名，如 `app:serve`
     * @param string             $description 简短描述
     * @param string             $usage       签名 DSL，留空时自动使用命令名
     * @param array<int, string> $aliases     命令别名
     * @param string|null        $group       所属分组
     * @param bool               $hidden      是否在命令列表中隐藏
     */
    public function __construct(
        public string $name,
        public string $description = '',
        public string $usage = '',
        public array $aliases = [],
        public ?string $group = null,
        public bool $hidden = false,
    ) {
    }
}
