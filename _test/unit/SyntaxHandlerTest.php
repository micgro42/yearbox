<?php

declare(strict_types=1);

namespace dokuwiki\plugin\yearbox\test\unit;

use dokuwiki\plugin\yearbox\src\SyntaxHandler;
use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @group plugin_yearbox
 * @group plugins
 */
final class SyntaxHandlerTest extends TestCase
{

    public function syntaxProvider(): Generator
    {
        yield 'year' => [ '{{yearbox>year=2010}}', [ 'year' => '2010'] ];
        yield 'ns' => [ '{{yearbox>ns=journal}}', [ 'ns' => ':journal'] ];
        yield 'ns with colon' => [ '{{yearbox>ns=foo:bar}}', [ 'ns' => 'foo:bar'] ];
        yield 'name' => ['{{yearbox>name=entry}}', ['name' => 'entry']];
        yield 'size' => ['{{yearbox>size=12}}', ['size' => '12']];
        yield 'fontsize' => ['{{yearbox>fontsize=12}}', ['size' => '12']];
        yield 'recent' => ['{{yearbox>recent=5}}', ['recent' => 5]];
        yield 'recent less than 0' => ['{{yearbox>recent=-5}}', ['recent' => 0]];
        yield 'months' => ['{{yearbox>months=foo,bar,baz}}', ['months' => ['foo','bar','baz']]];
        yield 'weekdays' => ['{{yearbox>weekdays=foo,bar,baz}}', ['weekdays' => ['foo','bar','baz']]];
        yield 'align left' => ['{{yearbox>align=left}}', ['align' => 'left']];
        yield 'align right' => ['{{yearbox>align=right}}', ['align' => 'right']];
        yield 'align invalid' => ['{{yearbox>align=invalid}}', []];
    }

    /**
     * @dataProvider syntaxProvider
     */
    public function testHandle(string $testSyntax, array $optOverrides): void
    {
        $handler = new SyntaxHandler($this->createStub(LoggerInterface::class));
        $expectedOpts = array_merge(
            $this->getDefaultOpts(),
            $optOverrides
        );

        $actualOpts = $handler->handle($testSyntax);

        self::assertSame($expectedOpts, $actualOpts);
    }

    public function testUnknownKey(): void {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $handler = new SyntaxHandler($loggerMock);
        $loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with("Yearbox Plugin: Unknown key 'foo' in '{{yearbox>foo=bar}}'");

        $actualOpts = $handler->handle('{{yearbox>foo=bar}}');

        self::assertSame($this->getDefaultOpts(), $actualOpts);
    }

    private function getDefaultOpts(): array {
        return [
            'ns' => '',
            'size' => 12,
            'name' => 'day',
            'year' => date('Y'),
            'recent' => false,
            'months' => [],
            'weekdays' => [],
            'align' => '',
        ];
    }
}
