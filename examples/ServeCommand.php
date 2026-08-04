<?php

declare(strict_types=1);

namespace Kode\Console\Examples;

use Kode\Console\Command;
use Kode\Console\Enum\ExitCode;
use Kode\Console\Input;
use Kode\Console\Output;

/**
 * 示例：构造函数式命令
 *
 * 演示带类型的选项、短别名与自动类型转换。
 */
final class ServeCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            'serve',
            '启动开发服务器',
            'serve {root? : 站点根目录} {--host=127.0.0.1 : 监听地址} {--port|-p:int=8080 : 监听端口} {--secure:bool : 使用 HTTPS}',
        );

        $this->alias(['server', 'start'])
            ->group('development')
            ->example('serve', '在当前目录启动服务器')
            ->example('serve ./public -p 8000', '指定目录与端口')
            ->related(['hello', 'db']);
    }

    #[\Override]
    public function fire(Input $in, Output $out): int
    {
        $root = (string) $in->arg('root', getcwd());

        if (!is_dir($root)) {
            return $this->fail($out, "站点根目录 '{$root}' 不存在。", ExitCode::InvalidInput);
        }

        $host = (string) $in->opt('host');
        $port = (int) $in->opt('port');           // 已按 :int 自动转换
        $scheme = $in->flag('secure') ? 'https' : 'http';

        $out->title('开发服务器');
        $out->table(['配置项', '值'], [
            ['配置项' => '根目录', '值' => $root],
            ['配置项' => '监听地址', '值' => "{$host}:{$port}"],
            ['配置项' => '协议', '值' => strtoupper($scheme)],
        ]);

        $out->success("服务器已启动: {$scheme}://{$host}:{$port}");
        $out->line('按 Ctrl+C 停止服务器');

        // 真实场景：passthru("php -S {$host}:{$port} -t " . escapeshellarg($root));

        return $this->ok();
    }
}
