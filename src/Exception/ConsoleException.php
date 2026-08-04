<?php

declare(strict_types=1);

namespace Kode\Console\Exception;

use RuntimeException;

/**
 * 控制台组件异常基类
 *
 * 调用方可以只捕获这一个类型来隔离控制台层面的错误。
 *
 * @package Kode\Console
 * @since 4.0.0
 */
class ConsoleException extends RuntimeException
{
}
