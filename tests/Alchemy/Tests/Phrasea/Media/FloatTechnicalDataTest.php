<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Media;

use Alchemy\Phrasea\Media\FloatTechnicalData;
use Alchemy\Phrasea\Media\TechnicalData;

final class FloatTechnicalDataTest extends \PHPUnit\Framework\TestCase
{
    public function testItImplementsTechnicalData()
    {
        $this->assertInstanceOf(TechnicalData::class, new FloatTechnicalData('foo', 3.14159));
    }

    public function testItReturnsItsName()
    {
        $sut = new FloatTechnicalData('foo', 3.14159);

        $this->assertSame('foo', $sut->getName());
    }

    public function testItReturnsItsValue()
    {
        $sut = new FloatTechnicalData('foo', '3.14159');

        $this->assertEquals(3.14159, $sut->getValue(), '', 0.00001);
    }
}
