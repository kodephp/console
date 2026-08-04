<?php

declare(strict_types=1);

namespace Kode\Console\Tests\Unit;

use Kode\Console\Definition\Argument;
use Kode\Console\Definition\Option;
use Kode\Console\Enum\ArgType;
use Kode\Console\Input;
use Kode\Console\Signature;
use Kode\Console\Tests\Support\Streams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Input::class)]
#[UsesClass(Signature::class)]
#[UsesClass(Argument::class)]
#[UsesClass(Option::class)]
#[UsesClass(ArgType::class)]
final class InputTest extends TestCase
{
    public function testParsesPositionalArguments(): void
    {
        $in = new Input(['build', 'src', 'dist']);

        self::assertSame('src', $in->arg(0));
        self::assertSame('dist', $in->arg(1));
        self::assertSame(['src', 'dist'], $in->positional());
        self::assertNull($in->arg(9));
        self::assertSame('fallback', $in->arg(9, 'fallback'));
    }

    public function testParsesLongOptionWithEqualSign(): void
    {
        $in = new Input(['serve', '--host=0.0.0.0']);

        self::assertSame('0.0.0.0', $in->opt('host'));
        self::assertTrue($in->provided('host'));
    }

    public function testParsesLongOptionWithSeparatedValueWhenSignatureDeclaresIt(): void
    {
        $in = new Input(['serve', '--host', '0.0.0.0'], new Signature('serve {--host=127.0.0.1}'));

        self::assertSame('0.0.0.0', $in->opt('host'));
        self::assertSame([], $in->positional());
    }

    public function testBooleanOptionKeepsFollowingTokenAsArgument(): void
    {
        $in = new Input(['serve', '--secure', 'public'], new Signature('serve {root?} {--secure:bool}'));

        self::assertTrue($in->flag('secure'));
        self::assertSame('public', $in->arg('root'));
    }

    public function testParsesCombinedShortFlags(): void
    {
        $in = new Input(['x', '-abc']);

        self::assertTrue($in->flag('a'));
        self::assertTrue($in->flag('b'));
        self::assertTrue($in->flag('c'));
        self::assertFalse($in->flag('d'));
    }

    public function testShortcutResolvesToLongOption(): void
    {
        $signature = new Signature('serve {--port|-p:int=80}');

        self::assertSame(9000, (new Input(['serve', '-p', '9000'], $signature))->opt('port'));
        self::assertSame(9001, (new Input(['serve', '-p9001'], $signature))->opt('port'));
        self::assertSame(9002, (new Input(['serve', '-p=9002'], $signature))->opt('port'));
        self::assertSame(80, (new Input(['serve'], $signature))->opt('port'));
    }

    public function testDoubleDashStopsOptionParsing(): void
    {
        $in = new Input(['run', 'task', '--', '--not-an-option', '-x']);

        self::assertSame(['--not-an-option', '-x'], $in->rest());
        self::assertFalse($in->flag('x'));
        self::assertSame('task', $in->arg(0));
    }

    public function testNegativeNumbersAreNotTreatedAsOptions(): void
    {
        $in = new Input(['offset', '-12']);

        self::assertSame('-12', $in->arg(0));
        self::assertFalse($in->flag('1'));
    }

    public function testBindsNamedArgumentsWithDefaultsAndCasting(): void
    {
        $in = new Input(['greet', 'Ada'], new Signature('greet {name} {times:int=2}'));

        self::assertSame('Ada', $in->arg('name'));
        self::assertSame(2, $in->arg('times'));
        self::assertSame(['name' => 'Ada', 'times' => 2], $in->args());
        self::assertTrue($in->has('name'));
    }

    public function testVariadicArgumentCollectsRemainingTokens(): void
    {
        $in = new Input(['cat', 'a.txt', 'b.txt', 'c.txt'], new Signature('cat {files*}'));

        self::assertSame(['a.txt', 'b.txt', 'c.txt'], $in->arg('files'));
    }

    public function testRepeatedOptionsAreAggregated(): void
    {
        $in = new Input(['x', '--tag=a', '--tag=b']);

        self::assertSame(['a', 'b'], $in->opt('tag'));
    }

    public function testProvidedDistinguishesDefaultsFromUserInput(): void
    {
        $signature = new Signature('serve {--host=127.0.0.1}');

        self::assertFalse((new Input(['serve'], $signature))->provided('host'));
        self::assertTrue((new Input(['serve', '--host=x'], $signature))->provided('host'));
    }

    public function testRawKeepsOriginalTokens(): void
    {
        $argv = ['greet', 'Ada', '--upper'];

        self::assertSame($argv, (new Input($argv))->raw());
    }

    #[DataProvider('castProvider')]
    public function testCast(string $type, mixed $value, mixed $expected): void
    {
        self::assertSame($expected, (new Input(['x']))->cast($value, $type));
    }

    /**
     * @return array<string, array{0: string, 1: mixed, 2: mixed}>
     */
    public static function castProvider(): array
    {
        return [
            'int' => ['int', '42', 42],
            'integer alias' => ['integer', '42abc', 0],
            'float' => ['float', '3.14', 3.14],
            'bool true' => ['bool', 'yes', true],
            'bool false' => ['bool', 'off', false],
            'array' => ['array', 'a, b ,c', ['a', 'b', 'c']],
            'json' => ['json', '{"a":1}', ['a' => 1]],
            'invalid json' => ['json', '{oops', null],
            'string' => ['string', 42, '42'],
            'null passthrough' => ['int', null, null],
        ];
    }

    public function testValidateRules(): void
    {
        $in = new Input(['x']);

        self::assertTrue($in->validate('p', '8080', ['required', 'numeric', 'int', 'min:1', 'max:65535']));
        self::assertFalse($in->validate('p', '', ['required']));
        self::assertFalse($in->validate('p', 'abc', ['numeric']));
        self::assertFalse($in->validate('p', '70000', ['max:65535']));
        self::assertTrue($in->validate('env', 'prod', ['in:dev,prod']));
        self::assertFalse($in->validate('env', 'test', ['in:dev,prod']));
        self::assertTrue($in->validate('name', 'ada', ['regex:/^[a-z]+$/']));
        self::assertTrue($in->validate('name', 'ada', ['unknown-rule']));
    }

    public function testInteractiveHelpersReadFromInjectedStream(): void
    {
        ob_start();
        $answer = Input::ask('姓名', 'World', Streams::withContent("Ada\n"));
        $fallback = Input::ask('姓名', 'World', Streams::withContent("\n"));
        $confirmed = Input::confirm('确定?', false, Streams::withContent("y\n"));
        $declined = Input::confirm('确定?', true, Streams::withContent("n\n"));
        $choice = Input::choice('环境', ['dev' => '开发', 'prod' => '生产'], 'dev', Streams::withContent("prod\n"));
        ob_end_clean();

        self::assertSame('Ada', $answer);
        self::assertSame('World', $fallback);
        self::assertTrue($confirmed);
        self::assertFalse($declined);
        self::assertSame('prod', $choice);
    }
}
