<?php

declare(strict_types=1);

namespace Kode\Console\Examples;

use Kode\Console\Attribute\AsCommand;
use Kode\Console\Command;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例：多操作命令
 *
 * 演示必填参数校验、枚举退出码与进度条。
 */
#[AsCommand(
    name: 'db',
    description: '数据库操作',
    usage: 'db {action : migrate|seed|backup|restore} {table? : 目标表} {--dsn= : 数据库连接串} {--force:bool : 跳过确认}',
    aliases: ['database'],
    group: 'database',
)]
final class DatabaseCommand extends Command
{
    private const array ACTIONS = ['migrate', 'seed', 'backup', 'restore'];

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $action = (string) $in->arg('action');

        if (!in_array($action, self::ACTIONS, true)) {
            return $this->fail(
                $out,
                "未知操作 '{$action}'，可用操作: " . implode(' / ', self::ACTIONS),
                ExitCode::InvalidInput,
            );
        }

        $table = $in->arg('table');
        $out->info(sprintf('执行 %s%s', $action, is_string($table) ? " -> {$table}" : ''));

        if ($in->provided('dsn')) {
            $out->debug('DSN: ' . (string) $in->opt('dsn'));
        }

        if (!$in->flag('force')) {
            $out->warn('未指定 --force，当前为演练模式。');
        }

        foreach (range(1, 5) as $step) {
            $out->progress($step, 5, 30, $action);
        }

        $out->success("{$action} 完成。");

        return $this->ok();
    }
}
