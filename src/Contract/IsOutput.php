<?php

namespace Kode\Console\Contract;

interface IsOutput
{
    /**
     * 输出一行文本
     */
    public function line(string $text, string $color = ''): void;

    /**
     * 输出信息消息
     */
    public function info(string $msg): void;

    /**
     * 输出警告消息
     */
    public function warn(string $msg): void;

    /**
     * 输出错误消息
     */
    public function error(string $msg): void;

    /**
     * 输出成功消息
     */
    public function success(string $msg): void;

    /**
     * 输出原始文本
     */
    public function raw(string $text): void;
}