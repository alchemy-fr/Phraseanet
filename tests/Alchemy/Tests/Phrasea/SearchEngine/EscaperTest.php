<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\Escaper;

/**
 * @group unit
 * @group searchengine
 */
class EscaperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider escapeRawProvider
     */
    public function testEscapeRaw($unescaped, $escaped)
    {
        $escaper = new Escaper();
        $this->assertEquals($escaped, $escaper->escapeRaw($unescaped));
    }

    public function escapeRawProvider()
    {
        return [
            ['foo', 'foo'],
            ['"', '\\"'],
            ['\\', '\\\\'],
            ['foo"bar\\baz', 'foo\"bar\\\\baz'],
        ];
    }
}
