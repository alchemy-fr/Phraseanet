<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;

/**
 * @group unit
 * @group searchengine
 */
class RecordHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider datesProvider
     */
    public function testSanitizeDate($in, $out)
    {
        $this->assertEquals($out, RecordHelper::sanitizeDate($in));
    }

    public function datesProvider()
    {
        return [
            ['', null],
            ['foo', null],
            ['55', '2055'],
            ['95', '1995'],
            ['2001/02','2001-02'],
            ['2001/02/03','2001-02-03'],
            ['2001/2/3','2001-02-03'],
            ['2001/2/3 4','2001-02-03 04:00:00'],
            ['2001/2/3 4.5','2001-02-03 04:05:00'],
            ['2001/2/3 4.5-6','2001-02-03 04:05:06'],
            ['2001/2/3 4.5-6-7', null],
            ['film de 10 minutes','2010']
        ];
    }
}
