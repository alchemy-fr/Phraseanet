<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\Escaper;

#[\PHPUnit\Framework\Attributes\Group('unit')]
#[\PHPUnit\Framework\Attributes\Group('searchengine')]
class EscaperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('escapeRawProvider')]
    public function testEscapeRaw($unescaped, $escaped)
    {
        $escaper = new Escaper();
        $this->assertEquals($escaped, $escaper->escapeRaw($unescaped));
    }

    public static function escapeRawProvider()
    {
        return [
            ['foo', 'foo'],
            ['"', '\\"'],
            ['\\', '\\\\'],
            ['foo"bar\\baz', 'foo\"bar\\\\baz'],
        ];
    }
}
