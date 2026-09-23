<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Enum\Verbosity;
use Kode\Console\Exception\CommandNotFoundException;
use Kode\Console\Input;
use Kode\Console\Kernel;
use Kode\Console\Middleware\LoggingMiddleware;
use Kode\Console\Output;
use Kode\Console\Tests\Fixture\AttributeCommand;
use Kode\Console\Tests\Fixture\ExplodingCommand;
use Kode\Console\Tests\Fixture\GreetCommand;
use Kode\Console\Tests\Fixture\HijackCommand;
use Kode\Console\Tests\Fixture\ListCommand;
use Kode\Console\Tests\Fixture\NoopCommand;
use Kode\Console\Tests\Fixture\RecordingEventManager;
use Kode\Console\Tests\Fixture\SpyMiddleware;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Kernel::class)]
#[CoversClass(GreetCommand::class)]
#[CoversClass(AttributeCommand::class)]
final class KernelTest extends TestCase
{
    private function makeOutput(): Output
    {
        return new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal);
    }

    private function kernel(): Kernel
    {
        return (new Kernel())->setOutput($this->makeOutput());
    }

    public function testRegisterAndFindWithAlias(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        self::assertInstanceOf(GreetCommand::class, $kernel->find('greet'));
        self::assertInstanceOf(GreetCommand::class, $kernel->find('hi'));
        self::assertTrue($kernel->has('greet'));
        self::assertFalse($kernel->has('nope'));
        self::assertArrayHasKey('greet', $kernel->all());
    }

    public function testResolveThrowsWithSuggestions(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        $this->expectException(CommandNotFoundException::class);

        try {
            $kernel->resolve('greetx');
        } catch (CommandNotFoundException $e) {
            self::assertContains('greet', $e->suggestions());
            throw $e;
        }
    }

    public function testBootListsCommandsAndHidesHidden(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class)->add(AttributeCommand::class);
        $code = $kernel->boot(['console']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('Kode Console ' . Kernel::VERSION, $content);
        self::assertStringContainsString('greet', $content);
        self::assertStringNotContainsString('attr:ping', $content);
    }

    public function testBootVersion(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', '--version']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString(\Kode\Console\Kernel::VERSION, $content);
    }

    public function testBootHelpForCommand(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'help', 'greet']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('用法:', $content);
        self::assertStringContainsString('greet', $content);
    }

    public function testBootRunsCommand(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greet', 'World']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(0, $code);
        self::assertStringContainsString('Hello World!', $content);
    }

    public function testBootMissingArgumentReturnsInvalidInput(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greet']);

        self::assertSame(2, $code);
        self::assertStringContainsString('缺少必填参数', Streams::read($kernel->getOutput()->getErrorStream()));
    }

    public function testBootUnknownCommandReturnsNotFound(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $code = $kernel->boot(['console', 'greetx']);

        $content = Streams::read($kernel->getOutput()->getStream());
        self::assertSame(127, $code);
        self::assertStringContainsString('你是不是想执行', $content);
        self::assertStringContainsString('greet', $content);
    }

    public function testExceptionPropagatesWhenNotCaught(): void
    {
        $kernel = (new Kernel())
            ->catchExceptions(false)
            ->setOutput($this->makeOutput())
            ->add(ExplodingCommand::class);

        $this->expectException(RuntimeException::class);

        $kernel->boot(['console', 'boom']);
    }

    public function testMiddlewareOrdering(): void
    {
        $kernel = $this->kernel()
            ->add(GreetCommand::class)
            ->addMiddleware(new SpyMiddleware('A'))
            ->addMiddleware(new SpyMiddleware('B'));

        $kernel->boot(['console', 'greet', 'World']);

        $content = Streams::read($kernel->getOutput()->getStream());
        $posA = strpos($content, '[MW:A]');
        $posB = strpos($content, '[MW:B]');
        $posHello = strpos($content, 'Hello World!');

        self::assertNotFalse($posA);
        self::assertNotFalse($posB);
        self::assertNotFalse($posHello);
        self::assertLessThan($posB, $posA);
        self::assertLessThan($posHello, $posB);
    }

    public function testLoggingMiddlewareEmitsDebugOnlyAtDebugVerbosity(): void
    {
        $quiet = (new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal));
        $kernel = (new Kernel())->setOutput($quiet)->add(GreetCommand::class)->addMiddleware(new LoggingMiddleware());
        $kernel->boot(['console', 'greet', 'World']);
        self::assertStringNotContainsString('命令开始执行', Streams::read($quiet->getStream()));
        self::assertStringContainsString('Hello World!', Streams::read($quiet->getStream()));

        $debug = (new Output(Streams::memory(), Streams::memory(), false, Verbosity::Debug));
        $kernel2 = (new Kernel())->setOutput($debug)->add(GreetCommand::class)->addMiddleware(new LoggingMiddleware());
        $kernel2->boot(['console', 'greet', 'World']);
        self::assertStringContainsString('命令开始执行', Streams::read($debug->getStream()));
    }

    // ------------------------------------------------------------------
    // 常驻进程跨 boot 状态
    // ------------------------------------------------------------------

    /**
     * 内核自持的 Output 不得把上一次的 -q 状态留给下一次 boot
     *
     * 框架把 Kernel 注册成容器单例；任何在同一进程内二次调用 boot()/run() 的代码
     * （计划任务、队列 worker、测试）都会继承上一轮的 verbosity，
     * 表现为「没传 -q 却完全没有输出」，且退出码仍是 0。
     */
    public function testKernelOwnedOutputIsRebuiltForEachBoot(): void
    {
        $kernel = new Kernel();
        $kernel->add(NoopCommand::class);

        $first = $kernel->getOutput();
        self::assertSame(0, $kernel->boot(['console', 'noop', '-q']));
        self::assertSame(Verbosity::Quiet, $kernel->getOutput()->getVerbosity());

        self::assertSame(0, $kernel->boot(['console', 'noop']));
        self::assertSame(Verbosity::Normal, $kernel->getOutput()->getVerbosity(), '第二次 boot 不应沿用上一轮的 Quiet');
        self::assertNotSame($first, $kernel->getOutput(), '内核自持的输出对象应每次 boot 重建');
    }

    /**
     * 外部注入的 Output 是调用方的资产，boot 不得抹掉它配好的 verbosity
     */
    public function testInjectedOutputKeepsItsOwnVerbosity(): void
    {
        $output = new Output(Streams::memory(), Streams::memory(), false, Verbosity::Debug);
        $kernel = (new Kernel())->setOutput($output)->add(NoopCommand::class);

        self::assertSame(0, $kernel->boot(['console', 'noop']));
        self::assertSame(Verbosity::Debug, $kernel->getOutput()->getVerbosity());
    }

    /**
     * `--` 之后的 token 按契约原样交给命令，不得被解释成全局标志
     *
     * Input 的注释承诺「-- 之后的内容原样保留」，而全局标志扫描是全量 argv 扫描：
     * `kode greet Ada -- -q` 会被静默消音并对管道返回 0（对脚本调用方是"空成功"）。
     */
    public function testDoubleDashShieldsTokensFromGlobalFlags(): void
    {
        $output = new Output(Streams::memory(), Streams::memory(), false, Verbosity::Normal);
        $kernel = (new Kernel())->setOutput($output)->add(AttributeCommand::class);

        // `--` 之后的 token 归命令所有（这里落进 host），但绝不能被内核当成全局 -q 消音。
        self::assertSame(0, $kernel->boot(['console', 'attr:ping', '--', '-q']));
        self::assertSame(Verbosity::Normal, $output->getVerbosity());
        self::assertStringContainsString('PING', Streams::read($output->getStream()), '-- 之后的 -q 不该消音');
    }

    // ------------------------------------------------------------------
    // 别名冲突
    // ------------------------------------------------------------------

    /**
     * 命令别名撞上已有命令名时必须报错，而不是静默错路由
     *
     * find() 先查别名表：静默注册成功意味着其中一个命令永远跑不到，
     * 而插件命令与主应用命令同表注册，跨插件同名别名是现实场景。
     */
    public function testAliasShadowingExistingCommandThrows(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        $this->expectException(\Kode\Console\Exception\InvalidCommandException::class);

        $kernel->add(HijackCommand::class);
    }

    public function testRegisteringCommandNameAlreadyTakenByAliasThrows(): void
    {
        $kernel = $this->kernel()->add(HijackCommand::class);

        $this->expectException(\Kode\Console\Exception\InvalidCommandException::class);

        $kernel->add(GreetCommand::class);
    }

    public function testAliasOverridingAnotherCommandsAliasThrows(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class)->add(AttributeCommand::class);

        $this->expectException(\Kode\Console\Exception\InvalidCommandException::class);

        // 'hi' 已经是 greet 的别名；改指向 attr:ping 会让 greet 的 hi 静默消失
        $kernel->alias('hi', 'attr:ping');
    }

    // ------------------------------------------------------------------
    // 帮助与退出码
    // ------------------------------------------------------------------

    /**
     * `help <不存在的命令>` 必须非零退出，否则 CI 里问错命令永远绿
     */
    public function testHelpForUnknownCommandExitsNonZero(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);

        self::assertSame(127, $kernel->boot(['console', 'help', 'greetx']));
    }

    public function testHelpForKnownCommandSucceeds(): void
    {
        $kernel = $this->kernel()->add(GreetCommand::class);
        $output = $kernel->getOutput();

        self::assertSame(0, $kernel->boot(['console', 'help', 'greet']));
        self::assertStringContainsString('greet', Streams::read($output->getStream()));
    }

    /**
     * 注册了名为 list 的命令时，内置帮助词不得把它永久遮蔽
     */
    public function testRegisteredListCommandIsReachable(): void
    {
        $kernel = $this->kernel()->add(ListCommand::class);

        self::assertSame(0, $kernel->boot(['console', 'list']));
        self::assertStringContainsString('LIST-RAN', Streams::read($kernel->getOutput()->getStream()));
    }

    // ------------------------------------------------------------------
    // 事件派发
    // ------------------------------------------------------------------

    /**
     * 命令执行前的事件异常按 catchExceptions 契约处理，不从 boot() 逃出
     */
    public function testExecutingListenerExceptionIsHandledByCatchExceptions(): void
    {
        $kernel = $this->kernel()
            ->add(GreetCommand::class)
            ->setEventManager(new RecordingEventManager('command.executing'));

        self::assertSame(1, $kernel->boot(['console', 'greet', 'Ada']));
    }

    /**
     * terminated 事件在一次 boot 内只派发一次
     *
     * 成功路径的 terminate() 在 try 内，监听器抛错会进 catch 再 terminate 一次；
     * 监听器（如指标上报、日志收尾）看到两次结束即重复结算。
     */
    public function testTerminatedEventDispatchedOnceEvenWhenCommandFails(): void
    {
        $events = new RecordingEventManager('kernel.terminated');
        $kernel = $this->kernel()->add(ExplodingCommand::class)->setEventManager($events);

        self::assertSame(1, $kernel->boot(['console', 'boom']));
        self::assertSame(1, $events->counts['kernel.terminated'] ?? 0, 'terminated 只应派发一次');
    }

    // ------------------------------------------------------------------
    // 全局标志的名字面
    // ------------------------------------------------------------------

    /**
     * 每个全局标志 token 的实际效果逐一钉死
     *
     * 预期按 token 字面写出，不从 GLOBAL_FLAGS 反推：否则表与 applyGlobalFlags()
     * 的分派同时写错（`--ansi` 被接到关闭着色）时，测试会跟着一起错。
     */
    public function testEveryGlobalFlagTokenTakesEffect(): void
    {
        /** @var array<string, array{0: Verbosity, 1: bool|null}> token => [verbosity, 着色（null = 不关心）] */
        $expected = [
            '-q' => [Verbosity::Quiet, null],
            '--quiet' => [Verbosity::Quiet, null],
            '-v' => [Verbosity::Verbose, null],
            '--verbose' => [Verbosity::Verbose, null],
            '-vv' => [Verbosity::Debug, null],
            '-vvv' => [Verbosity::Debug, null],
            '--debug' => [Verbosity::Debug, null],
            '--no-ansi' => [Verbosity::Normal, false],
            '--no-color' => [Verbosity::Normal, false],
            '--ansi' => [Verbosity::Normal, true],
        ];

        foreach ($expected as $token => [$verbosity, $decorated]) {
            self::assertArrayHasKey($token, Kernel::GLOBAL_FLAGS, "{$token} 已从全局标志表里丢失");

            // 着色断言要从「相反值」起步：初始就 false 时「--no-ansi 什么都不做」也能过
            $output = new Output(Streams::memory(), Streams::memory(), $decorated === false, Verbosity::Normal);
            $kernel = (new Kernel())->setOutput($output)->add(NoopCommand::class);

            self::assertSame(0, $kernel->boot(['console', 'noop', $token]));
            self::assertSame($verbosity, $output->getVerbosity(), "{$token} 没有设置预期的 verbosity");

            if ($decorated !== null) {
                self::assertSame($decorated, $output->isDecorated(), "{$token} 没有改变着色开关");
            }
        }
    }

    /**
     * globalFlagNames() 必须覆盖 Input 对这些 token 记账用的键
     *
     * 命令侧靠这个名字面放行内核标志（否则 `kode cmd -v` 会被自家命令判成未知选项）。
     * Input 按字符记账短选项，`-vv`/`-vvv` 与 `-v` 同样落成 `v`，这层折叠一旦漂移
     * 就会双向出错：该放行的被报错、不该放行的被静默忽略。
     */
    public function testGlobalFlagNamesCoverWhatInputRecords(): void
    {
        $names = Kernel::globalFlagNames();

        foreach (array_keys(Kernel::GLOBAL_FLAGS) as $token) {
            $input = new Input(['console', $token]);
            $recorded = array_keys($input->flags() + $input->options());

            self::assertNotSame([], $recorded, "{$token} 没有被 Input 记账，名字面无从放行");
            foreach ($recorded as $key) {
                self::assertContains($key, $names, "Input 把 {$token} 记成 {$key}，globalFlagNames() 却未放行");
            }
        }
    }

    /**
     * 名字面不能宽到把命令该报的未知选项也放行
     */
    public function testGlobalFlagNamesStayMinimal(): void
    {
        self::assertEqualsCanonicalizing(
            ['q', 'quiet', 'v', 'verbose', 'debug', 'no-ansi', 'no-color', 'ansi'],
            Kernel::globalFlagNames()
        );
    }
}
